<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Crm\Services\CrmContactService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/**
 * Komponente „CRM-Kontaktliste“: CRM-Kontakte eines Projekts je Komponente anlegen, verknüpfen,
 * bearbeiten und entfernen — intern wie extern.
 *
 * Regeln:
 *  - Nur die in der Komponente eingestellten Kontakttypen (gespiegelte Typen wie User/Freelancer
 *    sind nie erlaubt, die werden an ihrer Quelle gepflegt).
 *  - Externe sehen/füllen nur Eigenschaften aus nicht vertraulichen Gruppen und bearbeiten/entfernen
 *    nur Kontakte, die sie selbst angelegt haben. Vertrauliche Pflichtfelder sind für sie keine Pflicht.
 *  - Intern gelten die normalen CRM-Gruppenrechte (sichtbar/bearbeitbar).
 *  - Extern angelegte Kontakte sind „ungeprüft“, bis der Tab intern bestätigt wird.
 */
class ProjectComponentCrmContactService
{
    /** Gespiegelte Typen werden über ihr Quellprofil gepflegt, nicht frei angelegt. */
    public const MIRRORED_SLUGS = [
        CrmSystemContactTypeEnum::USER->value,
        CrmSystemContactTypeEnum::FREELANCER->value,
        CrmSystemContactTypeEnum::SERVICE_PROVIDER->value,
    ];

    public function __construct(
        private readonly CrmContactService $contactService,
        private readonly CrmPropertyGroupService $propertyGroupService,
        private readonly DatabaseManager $db,
    ) {
    }

    public function isCrmContactListComponent(Component $component): bool
    {
        return $component->type === ProjectTabComponentEnum::CRM_CONTACT_LIST->value;
    }

    /**
     * Kontakttypen, die in dieser Komponente angelegt/verknüpft werden dürfen (in Typ-Reihenfolge).
     *
     * @return Collection<int, CrmContactType>
     */
    public function allowedContactTypes(Component $component): Collection
    {
        $ids = array_values(array_filter(
            array_map('intval', (array) ($component->data['contact_type_ids'] ?? [])),
            static fn (int $id): bool => $id > 0,
        ));

        if ($ids === []) {
            return collect();
        }

        return CrmContactType::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereNotIn('slug', self::MIRRORED_SLUGS)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Aktive, frei anlegbare Kontakttypen — Auswahl in den Komponenten-Einstellungen.
     *
     * @return Collection<int, CrmContactType>
     */
    public function selectableContactTypes(): Collection
    {
        return CrmContactType::query()
            ->where('is_active', true)
            ->whereNotIn('slug', self::MIRRORED_SLUGS)
            ->orderBy('sort_order')
            ->get();
    }

    public function maxContacts(Component $component): ?int
    {
        $max = (int) ($component->data['max_contacts'] ?? 0);

        return $max > 0 ? $max : null;
    }

    /**
     * @return Collection<int, ProjectComponentCrmContact>
     */
    public function entries(Project $project, Component $component): Collection
    {
        return ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            // Kontakte im CRM-Papierkorb blenden wir aus (SoftDeletes-Scope der Relation)
            ->whereHas('crmContact')
            ->with([
                'crmContact.contactType',
                'crmContact.propertyValues',
                'createdByExternalAccess.crmContact',
                'createdByUser:id,first_name,last_name',
                'reviewedBy:id,first_name,last_name',
            ])
            ->orderBy('id')
            ->get();
    }

    public function findEntry(Project $project, Component $component, CrmContact $contact): ?ProjectComponentCrmContact
    {
        return ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->where('crm_contact_id', $contact->id)
            ->first();
    }

    /**
     * Eigenschaften, die eine externe Person sehen und füllen darf: alle aus nicht vertraulichen Gruppen.
     *
     * @return array<int, int>
     */
    public function externalPropertyIds(): array
    {
        return CrmProperty::query()
            ->whereHas('group', fn (Builder $group) => $group->where('is_confidential', false))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function visiblePropertyIdsFor(User $user): array
    {
        [$departmentIds, $isCrmManager] = $this->crmContextFor($user);

        return array_map('intval', $this->propertyGroupService->getVisiblePropertyIds(
            $user->id,
            $departmentIds,
            $isCrmManager,
        ));
    }

    /**
     * @return array<int, int>
     */
    public function editablePropertyIdsFor(User $user): array
    {
        [$departmentIds, $isCrmManager] = $this->crmContextFor($user);

        return array_map('intval', $this->propertyGroupService->getEditablePropertyIds(
            $user->id,
            $departmentIds,
            $isCrmManager,
        ));
    }

    /**
     * Anlage-/Bearbeitungsmaske eines Kontakttyps, beschränkt auf die übergebenen Eigenschaften.
     * Upload-Felder sind ausgenommen (brauchen einen bestehenden Kontakt und laufen extern nie).
     *
     * @param array<int, int> $allowedPropertyIds
     * @return array{contact_type: array<string, mixed>, groups: array<int, array<string, mixed>>}
     */
    public function mask(CrmContactType $contactType, array $allowedPropertyIds): array
    {
        $allowed = array_flip($allowedPropertyIds);
        $typeProperties = $contactType->properties()->with('group')->get()
            ->filter(fn (CrmProperty $property) => isset($allowed[$property->id])
                && $property->type !== CrmPropertyTypeEnum::UPLOAD);

        $groups = $typeProperties
            ->groupBy(fn (CrmProperty $property) => (int) $property->crm_property_group_id)
            ->map(function (Collection $properties) {
                /** @var CrmPropertyGroup|null $group */
                $group = $properties->first()->group;

                return [
                    'id' => $group?->id,
                    'name' => $group?->name,
                    'sort_order' => (int) $properties->min(fn (CrmProperty $p) => $p->pivot->sort_order ?? 0),
                    'properties' => $properties
                        ->sortBy(fn (CrmProperty $p) => $p->pivot->sort_order ?? 0)
                        ->values()
                        ->map(fn (CrmProperty $property) => [
                            'id' => $property->id,
                            'name' => $property->name,
                            'type' => $property->type->value,
                            'select_values' => $property->select_values,
                            'tooltip_text' => $property->tooltip_text,
                            'is_required' => (bool) ($property->pivot->is_required ?? false),
                        ])
                        ->all(),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();

        return [
            'contact_type' => $this->serializeContactType($contactType),
            'groups' => $groups,
        ];
    }

    /**
     * @param array<string|int, mixed> $propertyValues
     * @throws ValidationException
     */
    public function create(
        Project $project,
        Component $component,
        CrmContactType $contactType,
        string $displayName,
        array $propertyValues,
        User|ExternalAccess $actor,
    ): ProjectComponentCrmContact {
        $this->assertTypeAllowed($component, $contactType);
        $this->assertCapacity($project, $component);

        $allowedPropertyIds = $this->writablePropertyIdsFor($actor);
        $values = $this->filterValues($propertyValues, $allowedPropertyIds, $contactType);
        $this->validateRequired($contactType, $allowedPropertyIds, $displayName, $values);

        return $this->db->transaction(function () use (
            $project,
            $component,
            $contactType,
            $displayName,
            $values,
            $actor,
        ): ProjectComponentCrmContact {
            $contact = $this->contactService->store([
                'crm_contact_type_id' => $contactType->id,
                'display_name' => trim($displayName),
            ], $values);

            $isExternal = $actor instanceof ExternalAccess;
            if ($isExternal) {
                $contact->forceFill(['created_by_external_access_id' => $actor->id])->save();
            }

            $entry = ProjectComponentCrmContact::query()->create([
                'project_id' => $project->id,
                'component_id' => $component->id,
                'crm_contact_id' => $contact->id,
                'created_by_user_id' => $isExternal ? null : $actor->id,
                'created_by_external_access_id' => $isExternal ? $actor->id : null,
                // intern Angelegtes gilt als geprüft, extern Angelegtes erst mit der Bestätigung des Tabs
                'reviewed_at' => $isExternal ? null : now(),
                'reviewed_by_user_id' => $isExternal ? null : $actor->id,
            ]);

            $this->linkAsProjectArtist($project, $contact, $contactType);
            $this->logProjectHistory(
                $project,
                $actor,
                $isExternal ? 'External person {0} added contact {1}' : 'Contact {0} was added to the project',
                $isExternal ? [$actor->displayName(), $contact->display_name] : [$contact->display_name],
            );

            return $entry;
        });
    }

    /**
     * Bestehenden CRM-Kontakt verknüpfen (nur intern).
     */
    public function link(
        Project $project,
        Component $component,
        CrmContact $contact,
        User $actor,
    ): ProjectComponentCrmContact {
        $contact->loadMissing('contactType');
        if ($contact->contactType === null) {
            throw ValidationException::withMessages(['crm_contact_id' => __('This contact type is not allowed here.')]);
        }
        $this->assertTypeAllowed($component, $contact->contactType);

        $existing = $this->findEntry($project, $component, $contact);
        if ($existing !== null) {
            return $existing;
        }

        $this->assertCapacity($project, $component);

        return $this->db->transaction(function () use (
            $project,
            $component,
            $contact,
            $actor,
        ): ProjectComponentCrmContact {
            $entry = ProjectComponentCrmContact::query()->create([
                'project_id' => $project->id,
                'component_id' => $component->id,
                'crm_contact_id' => $contact->id,
                'created_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $actor->id,
            ]);

            $this->linkAsProjectArtist($project, $contact, $contact->contactType);
            $this->logProjectHistory(
                $project,
                $actor,
                'Contact {0} was added to the project',
                [$contact->display_name],
            );

            return $entry;
        });
    }

    /**
     * @param array<string|int, mixed> $propertyValues
     * @throws ValidationException
     */
    public function update(
        ProjectComponentCrmContact $entry,
        ?string $displayName,
        array $propertyValues,
        User|ExternalAccess $actor,
    ): ProjectComponentCrmContact {
        $this->assertCanEdit($entry, $actor);

        $contact = $entry->crmContact()->with('contactType')->firstOrFail();
        $contactType = $contact->contactType;
        if ($contactType === null || in_array($contactType->slug, self::MIRRORED_SLUGS, true)) {
            abort(403, __('Mirrored contacts can only be changed via their profile.'));
        }

        $allowedPropertyIds = $this->writablePropertyIdsFor($actor);
        $values = $this->filterValues($propertyValues, $allowedPropertyIds, $contactType);
        $name = $displayName !== null ? trim($displayName) : $contact->display_name;
        $this->validateRequired($contactType, $allowedPropertyIds, $name, $values, $contact);

        $this->db->transaction(function () use ($contact, $name, $values): void {
            $this->contactService->update($contact, ['display_name' => $name], $values);
        });

        return $entry->refresh();
    }

    /**
     * Intern: Verknüpfung lösen (der Kontakt bleibt im CRM).
     * Extern: nur eigene Kontakte; nicht anderweitig genutzte werden zusätzlich gelöscht (CRM-Papierkorb).
     */
    public function remove(ProjectComponentCrmContact $entry, User|ExternalAccess $actor): void
    {
        $this->assertCanEdit($entry, $actor);

        $this->db->transaction(function () use ($entry, $actor): void {
            /** @var CrmContact|null $contact */
            $contact = $entry->crmContact()->first();
            $project = $entry->project()->first();
            $entry->delete();

            if ($contact === null || $project === null) {
                return;
            }

            if ($actor instanceof ExternalAccess) {
                if (!$this->isUsedElsewhere($contact, $project)) {
                    $project->crmContacts()->detach($contact->id);
                    $contact->delete();
                }
                $this->logProjectHistory(
                    $project,
                    $actor,
                    'External person {0} removed contact {1}',
                    [$actor->displayName(), $contact->display_name],
                );

                return;
            }

            $this->logProjectHistory(
                $project,
                $actor,
                'Contact {0} was removed from the project',
                [$contact->display_name],
            );
        });
    }

    /**
     * Mit der internen Bestätigung eines Tabs gelten die Kontakte dieser Person im Projekt als geprüft.
     */
    public function markReviewedForExternal(Project $project, ExternalAccess $external, User $reviewer): int
    {
        return ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('created_by_external_access_id', $external->id)
            ->whereNull('reviewed_at')
            ->update(['reviewed_at' => now(), 'reviewed_by_user_id' => $reviewer->id]);
    }

    public function countCreatedByExternal(Project $project, ExternalAccess $external): int
    {
        return ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('created_by_external_access_id', $external->id)
            ->whereHas('crmContact')
            ->count();
    }

    /**
     * Verknüpfbare CRM-Kontakte der erlaubten Typen (ohne bereits verknüpfte).
     *
     * @return Collection<int, CrmContact>
     */
    public function searchLinkable(Project $project, Component $component, string $search): Collection
    {
        $typeIds = $this->allowedContactTypes($component)->pluck('id')->all();
        if ($typeIds === []) {
            return collect();
        }

        $linkedIds = ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->pluck('crm_contact_id')
            ->all();

        return CrmContact::query()
            ->with('contactType')
            ->whereIn('crm_contact_type_id', $typeIds)
            ->whereNotIn('id', $linkedIds)
            ->when(
                trim($search) !== '',
                fn (Builder $q) => $q->where('display_name', 'like', '%' . trim($search) . '%'),
            )
            ->orderBy('display_name')
            ->limit(20)
            ->get();
    }

    /**
     * Mögliche Dubletten eines (ungeprüften) Kontakts: gleicher Typ, gleicher Name oder gleiche E-Mail.
     *
     * @return array<int, array{id: int, display_name: string}>
     */
    public function possibleDuplicates(CrmContact $contact): array
    {
        $name = mb_strtolower(trim((string) $contact->display_name));
        $emailPropertyIds = CrmProperty::query()->where('name', 'Email')->pluck('id')->all();
        $email = $emailPropertyIds === []
            ? ''
            : mb_strtolower(trim((string) $contact->propertyValues
                ->first(fn (CrmPropertyValue $value) => in_array($value->crm_property_id, $emailPropertyIds, false))
                ?->value));

        if ($name === '' && $email === '') {
            return [];
        }

        return CrmContact::query()
            ->where('crm_contact_type_id', $contact->crm_contact_type_id)
            ->where('id', '!=', $contact->id)
            ->where(function (Builder $query) use ($name, $email, $emailPropertyIds): void {
                if ($name !== '') {
                    $query->whereRaw('LOWER(TRIM(display_name)) = ?', [$name]);
                }
                if ($email !== '') {
                    $query->orWhereHas('propertyValues', fn (Builder $value) => $value
                        ->whereIn('crm_property_id', $emailPropertyIds)
                        ->whereRaw('LOWER(TRIM(value)) = ?', [$email]));
                }
            })
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'display_name'])
            ->map(fn (CrmContact $duplicate) => [
                'id' => $duplicate->id,
                'display_name' => $duplicate->display_name,
            ])
            ->all();
    }

    /**
     * Karte eines Kontakts. $visiblePropertyIds begrenzt die ausgegebenen Werte (Vertraulichkeit).
     *
     * @param array<int, int> $visiblePropertyIds
     * @return array<string, mixed>
     */
    public function serializeEntry(
        ProjectComponentCrmContact $entry,
        array $visiblePropertyIds,
        User|ExternalAccess $viewer,
        bool $canWrite,
    ): array {
        $contact = $entry->crmContact;
        $visible = array_flip($visiblePropertyIds);
        $typeProperties = $contact->contactType?->properties()->with('group')->get() ?? collect();
        $valuesByProperty = $contact->propertyValues->keyBy('crm_property_id');

        $fields = $typeProperties
            ->filter(fn (CrmProperty $property) => isset($visible[$property->id])
                && $property->type !== CrmPropertyTypeEnum::UPLOAD
                && trim((string) $valuesByProperty->get($property->id)?->value) !== '')
            ->map(fn (CrmProperty $property) => [
                'property_id' => $property->id,
                'name' => $property->name,
                'type' => $property->type->value,
                'group' => $property->group?->name,
                'value' => $valuesByProperty->get($property->id)?->value,
            ])
            ->values()
            ->all();

        $values = [];
        foreach ($fields as $field) {
            $values[$field['property_id']] = $field['value'];
        }

        $isExternalViewer = $viewer instanceof ExternalAccess;
        $createdByExternal = $entry->createdByExternalAccess;

        return [
            'id' => $contact->id,
            'display_name' => $contact->display_name,
            'profile_photo_url' => $isExternalViewer ? null : $contact->profile_photo_url,
            'contact_type' => $contact->contactType ? $this->serializeContactType($contact->contactType) : null,
            'fields' => $fields,
            'values' => (object) $values,
            'created_at' => $entry->created_at?->toIso8601String(),
            'created_by_external' => $createdByExternal !== null && !$isExternalViewer
                ? ['id' => $createdByExternal->id, 'name' => $createdByExternal->displayName()]
                : null,
            'created_by_me' => $isExternalViewer && $entry->isCreatedByExternal($viewer),
            'is_external' => $entry->created_by_external_access_id !== null,
            'reviewed_at' => $entry->reviewed_at?->toIso8601String(),
            'reviewed_by' => $entry->reviewedBy?->full_name,
            'can_edit' => $canWrite && $this->canEdit($entry, $viewer),
            'possible_duplicates' => !$isExternalViewer && $entry->created_by_external_access_id !== null
                && $entry->reviewed_at === null
                ? $this->possibleDuplicates($contact)
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeContactType(CrmContactType $contactType): array
    {
        return [
            'id' => $contactType->id,
            'name' => $contactType->name,
            'slug' => $contactType->slug,
            'color' => $contactType->color,
            'icon' => $contactType->icon,
        ];
    }

    public function canEdit(ProjectComponentCrmContact $entry, User|ExternalAccess $actor): bool
    {
        if ($actor instanceof ExternalAccess) {
            return $entry->isCreatedByExternal($actor);
        }

        return true;
    }

    /**
     * @return array<int, int>
     */
    public function writablePropertyIdsFor(User|ExternalAccess $actor): array
    {
        return $actor instanceof ExternalAccess
            ? $this->externalPropertyIds()
            : $this->editablePropertyIdsFor($actor);
    }

    private function assertCanEdit(ProjectComponentCrmContact $entry, User|ExternalAccess $actor): void
    {
        if (!$this->canEdit($entry, $actor)) {
            abort(403, __('You can only change contacts you have added yourself.'));
        }
    }

    private function assertTypeAllowed(Component $component, CrmContactType $contactType): void
    {
        $allowed = $this->allowedContactTypes($component)->contains('id', $contactType->id);
        if (!$allowed) {
            throw ValidationException::withMessages([
                'crm_contact_type_id' => __('This contact type is not allowed here.'),
            ]);
        }
    }

    private function assertCapacity(Project $project, Component $component): void
    {
        $max = $this->maxContacts($component);
        if ($max === null) {
            return;
        }

        $count = ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->whereHas('crmContact')
            ->count();

        if ($count >= $max) {
            throw ValidationException::withMessages([
                'crm_contact_type_id' => __('The maximum number of contacts (:max) has been reached.', ['max' => $max]),
            ]);
        }
    }

    /**
     * Nur Eigenschaften des Typs, die der/die Handelnde schreiben darf; Uploads nie.
     *
     * @param array<string|int, mixed> $propertyValues
     * @param array<int, int> $allowedPropertyIds
     * @return array<int, string|null>
     */
    private function filterValues(array $propertyValues, array $allowedPropertyIds, CrmContactType $contactType): array
    {
        $allowed = array_flip($allowedPropertyIds);
        $typeProperties = $contactType->properties()->get()->keyBy('id');

        $values = [];
        foreach ($propertyValues as $propertyId => $value) {
            $propertyId = (int) $propertyId;
            $property = $typeProperties->get($propertyId);
            $isWritable = isset($allowed[$propertyId])
                && $property !== null
                && $property->type !== CrmPropertyTypeEnum::UPLOAD;
            if (!$isWritable) {
                continue;
            }
            if (is_array($value)) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $values[$propertyId] = $value !== null ? (string) $value : null;
        }

        return $values;
    }

    /**
     * Pflichtfelder der Maske prüfen (nur Felder, die der/die Handelnde überhaupt sieht — vertrauliche
     * Pflichtfelder sind für Externe keine Pflicht). Beim Bearbeiten zählen auch schon gespeicherte Werte.
     *
     * @param array<int, int> $allowedPropertyIds
     * @param array<int, string|null> $values
     * @throws ValidationException
     */
    private function validateRequired(
        CrmContactType $contactType,
        array $allowedPropertyIds,
        string $displayName,
        array $values,
        ?CrmContact $existing = null,
    ): void {
        $errors = [];
        if (trim($displayName) === '') {
            $errors['display_name'] = __('This is a mandatory field.');
        }

        $existingValues = $existing?->propertyValues()->pluck('value', 'crm_property_id')->all() ?? [];
        $allowed = array_flip($allowedPropertyIds);

        foreach ($contactType->properties()->get() as $property) {
            if (!($property->pivot->is_required ?? false) || !isset($allowed[$property->id])) {
                continue;
            }
            if ($property->type === CrmPropertyTypeEnum::UPLOAD) {
                continue;
            }
            $value = array_key_exists($property->id, $values)
                ? $values[$property->id]
                : ($existingValues[$property->id] ?? null);
            if ($value === null || trim((string) $value) === '') {
                $errors['property_values.' . $property->id] = __('This is a mandatory field.');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Künstler*innen erscheinen zusätzlich als Projekt-Künstler*innen (Projektsuche, Kopfdaten).
     */
    private function linkAsProjectArtist(Project $project, CrmContact $contact, CrmContactType $contactType): void
    {
        if ($contactType->slug === CrmSystemContactTypeEnum::ARTIST->value) {
            $project->crmContacts()->syncWithoutDetaching([$contact->id]);
        }
    }

    /**
     * Wird der Kontakt außerhalb dieses Projekts genutzt (andere Listen/Projekte, Team, Aufenthalte,
     * Zugänge)? Dann entfernt eine externe Person nur die Verknüpfung.
     */
    private function isUsedElsewhere(CrmContact $contact, Project $project): bool
    {
        $id = $contact->id;

        return ProjectComponentCrmContact::query()->where('crm_contact_id', $id)->exists()
            || $this->db->table('crm_contact_project')->where('crm_contact_id', $id)
                ->where('project_id', '!=', $project->id)->exists()
            || $this->db->table('crm_contact_project_team')->where('crm_contact_id', $id)->exists()
            || $this->db->table('artist_residencies')->where('artist_crm_contact_id', $id)->exists()
            || $this->db->table('external_accesses')->where('crm_contact_id', $id)->exists();
    }

    /**
     * Projektverlauf im gleichen Property-Format wie ChangeBuilder (ProjectHistoryComponent).
     *
     * @param array<int, string> $placeholders
     */
    private function logProjectHistory(
        Project $project,
        User|ExternalAccess $actor,
        string $translationKey,
        array $placeholders,
    ): void {
        Activity::query()->create([
            'log_name' => 'project',
            'description' => $translationKey,
            'subject_type' => $project->getMorphClass(),
            'subject_id' => $project->id,
            'event' => 'updated',
            'causer_type' => $actor->getMorphClass(),
            'causer_id' => $actor->id,
            'properties' => [[
                'type' => 'project',
                'translationKey' => $translationKey,
                'translationKeyPlaceholderValues' => $placeholders,
            ]],
        ]);
    }

    /**
     * @return array{0: array<int, int>, 1: bool}
     */
    private function crmContextFor(User $user): array
    {
        return [
            $user->departments?->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [],
            $user->can(PermissionEnum::CRM_MANAGER->value),
        ];
    }
}
