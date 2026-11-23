<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Repositories\CrmContactRepository;
use Artwork\Modules\Crm\Repositories\CrmPropertyValueRepository;
use Artwork\Modules\Crm\Support\CrmPropertyValueFormat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

readonly class CrmContactService
{
    public function __construct(
        private CrmContactRepository $contactRepository,
        private CrmPropertyValueRepository $propertyValueRepository,
    ) {
    }

    public function getByType(
        int $typeId,
        ?string $search = null,
        int $perPage = 15,
        array $filters = [],
        array $allowedPropertyIds = [],
        ?string $sort = null,
        string $direction = 'asc'
    ): LengthAwarePaginator {
        return $this->contactRepository->getByType(
            $typeId,
            $search,
            $perPage,
            $filters,
            $allowedPropertyIds,
            $sort,
            $direction
        );
    }

    public function searchForLinking(?string $search = null, ?int $typeId = null, int $limit = 20): Collection
    {
        return $this->contactRepository->searchForLinking($search, $typeId, $limit);
    }

    public function findById(int $id): ?CrmContact
    {
        return $this->contactRepository->findById($id);
    }

    public function store(array $data, array $propertyValues = []): CrmContact
    {
        $contact = $this->contactRepository->create([
            'crm_contact_type_id' => $data['crm_contact_type_id'],
            'display_name' => $data['display_name'],
            'profile_image' => $data['profile_image'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->ensureAccommodationEntity($contact);

        $this->savePropertyValues($contact, $propertyValues);

        return $contact;
    }

    public function update(CrmContact $contact, array $data, array $propertyValues = []): CrmContact
    {
        $this->contactRepository->update($contact, array_filter([
            'display_name' => $data['display_name'] ?? null,
            'profile_image' => $data['profile_image'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        $this->savePropertyValues($contact, $propertyValues);

        // Unterkünfte, Künstler*innen und Hersteller werden im CRM gepflegt: der neue Name gilt auch für den
        // verknüpften Datensatz, sonst setzte der Abgleich unten den Kontakt auf den alten Namen zurück.
        $entity = $contact->getSourceEntity();
        $isNamedInCrm = $entity instanceof Accommodation
            || $entity instanceof Artist
            || $entity instanceof Manufacturer;
        if ($isNamedInCrm && !empty($data['display_name'])) {
            $entity->forceFill(['name' => $data['display_name']])->save();
        }

        // Sync display_name from source entity if available
        $this->syncDisplayName($contact);

        return $contact->fresh(['contactType', 'propertyValues.property']);
    }

    public function destroy(CrmContact $contact): void
    {
        $this->contactRepository->delete($contact);
    }

    /**
     * Wechselt den Kontakttyp. Werte von Eigenschaften, die dem neuen Typ nicht
     * zugewiesen sind, werden gelöscht — das Frontend weist vorher darauf hin.
     */
    public function changeType(CrmContact $contact, \Artwork\Modules\Crm\Models\CrmContactType $newType): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($contact, $newType): void {
            $keptPropertyIds = $newType->properties()->pluck('crm_properties.id')->toArray();

            $droppedPropertyIds = $contact->propertyValues()
                ->whereNotIn('crm_property_id', $keptPropertyIds)
                ->pluck('crm_property_id')
                ->all();
            $fileService = app(CrmPropertyFileService::class);
            $droppedFiles = $fileService->collectPathsOfProperties($droppedPropertyIds, (int) $contact->id);

            $contact->propertyValues()
                ->whereNotIn('crm_property_id', $keptPropertyIds)
                ->delete();

            \Illuminate\Support\Facades\DB::afterCommit(static fn () => $fileService->deleteMany($droppedFiles));

            $this->contactRepository->update($contact, ['crm_contact_type_id' => $newType->id]);
            $contact->setRelation('contactType', $newType);

            $this->ensureAccommodationEntity($contact);
        });
    }

    /**
     * Künstler*innenaufenthalte und Zimmertypen hängen am Unterkunfts-Datensatz. Ein Kontakt der
     * Systemart Unterkunft bekommt ihn deshalb gleich beim Anlegen (Dialog, Import, Typwechsel),
     * verknüpft wie bei den aus dem Altbestand übernommenen Unterkünften. Bereits vorhandene
     * Eigenschaftswerte (Typwechsel) gehen in den Datensatz ein, sonst leerte dessen nächstes
     * Speichern sie im CRM.
     */
    private function ensureAccommodationEntity(CrmContact $contact): void
    {
        if (
            $contact->entity_id !== null
            || $contact->contactType?->slug !== CrmSystemContactTypeEnum::ACCOMMODATION->value
        ) {
            return;
        }

        $accommodation = Accommodation::create(array_merge(
            $this->accommodationFieldsFromPropertyValues($contact),
            ['name' => $contact->display_name]
        ));
        $accommodation->forceFill(['crm_contact_id' => $contact->id])->saveQuietly();

        $this->contactRepository->update($contact, [
            'entity_type' => $accommodation->getMorphClass(),
            'entity_id' => $accommodation->getKey(),
        ]);
        $contact->setRelation('entity', $accommodation);
    }

    /**
     * @return array<string, string>
     */
    private function accommodationFieldsFromPropertyValues(CrmContact $contact): array
    {
        $columns = (new Accommodation())->getCrmFields();
        $fields = [];

        foreach ($contact->propertyValues()->with('property')->get() as $propertyValue) {
            $property = $propertyValue->property;
            $column = $property ? ($columns[$property->name] ?? null) : null;

            if (
                !is_string($column)
                || $propertyValue->value === null
                || $propertyValue->value === ''
                || CrmPropertyValueFormat::isMultiple($property)
            ) {
                continue;
            }

            $fields[$column] = $propertyValue->value;
        }

        return $fields;
    }

    public function updateProfileImage(CrmContact $contact, ?string $path): void
    {
        $this->contactRepository->update($contact, ['profile_image' => $path]);
    }

    public function savePropertyValue(CrmContact $contact, int $propertyId, ?string $value): void
    {
        $this->propertyValueRepository->updateOrCreate(
            $contact->id,
            $propertyId,
            $value
        );

        $this->writeBackToSource($contact, $propertyId, $value);
    }

    /**
     * Upload-Eigenschaft setzen oder leeren: Pfad auf der Disk plus Anzeigename der Datei.
     */
    public function savePropertyFile(CrmContact $contact, int $propertyId, ?string $path, ?string $fileName): void
    {
        $this->propertyValueRepository->updateOrCreateFile($contact->id, $propertyId, $path, $fileName);

        $this->writeBackToSource($contact, $propertyId, $path);
    }

    /**
     * Nur der Anzeigename ändert sich; die Datei behält ihren Hash-Namen auf der Disk.
     */
    public function renamePropertyFile(CrmContact $contact, int $propertyId, string $fileName): void
    {
        $contact->propertyValues()
            ->where('crm_property_id', $propertyId)
            ->update(['file_name' => $fileName]);
    }

    private function writeBackToSource(CrmContact $contact, int $propertyId, ?string $value): void
    {
        $entity = $contact->getSourceEntity();

        if (!$entity) {
            return;
        }

        $property = CrmProperty::find($propertyId);

        // Quell-Datensätze (User/Freelancer/…) kennen keine Mehrfachauswahl
        if (!$property || CrmPropertyValueFormat::isMultiple($property)) {
            return;
        }

        $entity->setCrmFieldValue($property->name, $value);
    }

    private function syncDisplayName(CrmContact $contact): void
    {
        $entity = $contact->getSourceEntity();

        if (!$entity) {
            return;
        }

        $entity->refresh();
        $displayName = $entity->getCrmDisplayName();

        if ($displayName && $contact->display_name !== $displayName) {
            $contact->update(['display_name' => $displayName]);
        }
    }

    private function savePropertyValues(CrmContact $contact, array $propertyValues): void
    {
        $properties = CrmProperty::query()->whereKey(array_keys($propertyValues))->get()->keyBy('id');

        foreach ($propertyValues as $propertyId => $value) {
            // Dateien kommen nur über die Upload-Endpunkte, nie als frei gesetzter Pfad
            if ($properties->get((int) $propertyId)?->type === CrmPropertyTypeEnum::UPLOAD) {
                continue;
            }

            $this->savePropertyValue(
                $contact,
                (int) $propertyId,
                // Mehrfachauswahl als JSON-Liste, alles andere als Text
                CrmPropertyValueFormat::toStorage($properties->get((int) $propertyId), $value)
            );
        }
    }
}
