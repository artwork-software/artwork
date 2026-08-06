<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class CrmDuplicateService
{
    // Gespiegelte Typen werden nicht zusammengeführt — deren Duplikate müssen an der
    // Quelle (User-/Freelancer-/Dienstleister-Verwaltung) bereinigt werden.
    private const MIRRORED_SLUGS = [
        CrmSystemContactTypeEnum::USER->value,
        CrmSystemContactTypeEnum::FREELANCER->value,
        CrmSystemContactTypeEnum::SERVICE_PROVIDER->value,
    ];

    public function __construct(
        private CrmContactService $contactService,
    ) {}

    /**
     * Findet Duplikat-Cluster: Kontakte desselben Typs mit gleichem Namen oder
     * gleicher E-Mail (Eigenschaft "Email"), case-insensitiv.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDuplicateGroups(): array
    {
        $types = CrmContactType::query()
            ->whereNotIn('slug', self::MIRRORED_SLUGS)
            ->get()
            ->keyBy('id');

        if ($types->isEmpty()) {
            return [];
        }

        $emailPropertyIds = CrmProperty::query()->where('name', 'Email')->pluck('id')->all();

        $contacts = CrmContact::query()
            ->whereIn('crm_contact_type_id', $types->keys())
            ->with(['propertyValues' => fn ($q) => $q->whereIn('crm_property_id', $emailPropertyIds ?: [0])])
            ->orderBy('id')
            ->get();

        $byName = [];
        $byEmail = [];

        foreach ($contacts as $contact) {
            $nameKey = mb_strtolower(trim((string) $contact->display_name));
            if ($nameKey !== '') {
                $byName[$contact->crm_contact_type_id . '|' . $nameKey][] = $contact;
            }

            $email = trim((string) $contact->propertyValues->first()?->value);
            if ($email !== '') {
                $byEmail[$contact->crm_contact_type_id . '|' . mb_strtolower($email)][] = $contact;
            }
        }

        $clusters = [];
        $seenIdSets = [];

        $pushCluster = function (string $match, string $key, array $group) use (&$clusters, &$seenIdSets, $types): void {
            if (count($group) < 2) {
                return;
            }

            $idSet = collect($group)->pluck('id')->sort()->implode(',');
            if (isset($seenIdSets[$idSet])) {
                return;
            }
            $seenIdSets[$idSet] = true;

            [$typeId, $value] = explode('|', $key, 2);
            $type = $types->get((int) $typeId);

            $clusters[] = [
                'match' => $match,
                'value' => $value,
                'contact_type' => $type ? [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'icon' => $type->icon,
                    'color' => $type->color,
                ] : null,
                'contacts' => collect($group)->map(fn (CrmContact $contact) => [
                    'id' => $contact->id,
                    'display_name' => $contact->display_name,
                    'email' => $contact->propertyValues->first()?->value,
                    'created_at' => $contact->created_at?->format('d.m.Y'),
                    'profile_photo_url' => $contact->profile_photo_url,
                    // Entity-gebundene Kontakte dürfen nur Hauptkontakt sein
                    'has_entity' => $contact->entity_type !== null,
                ])->values()->all(),
            ];
        };

        foreach ($byName as $key => $group) {
            $pushCluster('name', $key, $group);
        }
        foreach ($byEmail as $key => $group) {
            $pushCluster('email', $key, $group);
        }

        return $clusters;
    }

    /**
     * Führt Duplikate in den Hauptkontakt zusammen:
     * - Eigenschaftswerte ergänzen den Hauptkontakt nur dort, wo er leer ist
     * - Projekt-/Team-/Aufenthalts-/Zugriffs-Verknüpfungen werden umgehängt
     * - Duplikate landen (soft-deleted) im Papierkorb
     *
     * @param Collection<int, CrmContact> $duplicates
     */
    public function merge(CrmContact $primary, Collection $duplicates): void
    {
        DB::transaction(function () use ($primary, $duplicates): void {
            $primaryValues = $primary->propertyValues()->get()->keyBy('crm_property_id');

            foreach ($duplicates as $duplicate) {
                foreach ($duplicate->propertyValues()->get() as $value) {
                    $existing = $primaryValues->get($value->crm_property_id);
                    $hasValue = $existing !== null && trim((string) $existing->value) !== '';

                    if (!$hasValue && trim((string) $value->value) !== '') {
                        $this->contactService->savePropertyValue(
                            $primary,
                            $value->crm_property_id,
                            $value->value
                        );
                        $primaryValues->put($value->crm_property_id, $value);
                    }
                }

                if (!$primary->profile_image && $duplicate->profile_image) {
                    $primary->update(['profile_image' => $duplicate->profile_image]);
                    // Bild bleibt im Storage und gehört jetzt dem Hauptkontakt
                    $duplicate->profile_image = null;
                    $duplicate->saveQuietly();
                }

                $this->reassignProjectPivot('crm_contact_project', $primary, $duplicate);
                $this->reassignProjectPivot('crm_contact_project_team', $primary, $duplicate, mergeRoles: true);

                DB::table('artist_residencies')
                    ->where('artist_crm_contact_id', $duplicate->id)
                    ->update(['artist_crm_contact_id' => $primary->id]);
                DB::table('artist_residencies')
                    ->where('accommodation_crm_contact_id', $duplicate->id)
                    ->update(['accommodation_crm_contact_id' => $primary->id]);

                DB::table('external_accesses')
                    ->where('crm_contact_id', $duplicate->id)
                    ->update(['crm_contact_id' => $primary->id]);

                DB::table('document_requests')
                    ->where('crm_contact_id', $duplicate->id)
                    ->update(['crm_contact_id' => $primary->id]);

                $duplicate->delete();
            }
        });
    }

    /**
     * Hängt Projekt-Pivot-Zeilen des Duplikats auf den Hauptkontakt um. Ist das
     * Projekt am Hauptkontakt schon verknüpft, wird die Duplikat-Zeile verworfen
     * (bei $mergeRoles werden die Rollen-Arrays vorher vereinigt).
     */
    private function reassignProjectPivot(string $table, CrmContact $primary, CrmContact $duplicate, bool $mergeRoles = false): void
    {
        $duplicateRows = DB::table($table)->where('crm_contact_id', $duplicate->id)->get();

        foreach ($duplicateRows as $row) {
            $primaryRow = DB::table($table)
                ->where('crm_contact_id', $primary->id)
                ->where('project_id', $row->project_id)
                ->first();

            if ($primaryRow === null) {
                DB::table($table)->where('id', $row->id)->update(['crm_contact_id' => $primary->id]);
                continue;
            }

            if ($mergeRoles) {
                $merged = collect(json_decode((string) $primaryRow->roles, true) ?: [])
                    ->merge(json_decode((string) $row->roles, true) ?: [])
                    ->unique()
                    ->values()
                    ->all();

                DB::table($table)->where('id', $primaryRow->id)->update(['roles' => json_encode($merged)]);
            }

            DB::table($table)->where('id', $row->id)->delete();
        }
    }
}
