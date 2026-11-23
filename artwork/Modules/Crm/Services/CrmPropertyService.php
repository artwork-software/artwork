<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Exceptions\CrmSettingLockedException;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Crm\Repositories\CrmPropertyRepository;
use Artwork\Modules\Crm\Support\CrmPropertyValueFormat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

readonly class CrmPropertyService
{
    public function __construct(
        private CrmPropertyRepository $repository,
        private CrmPropertyFileService $fileService,
    ) {
    }

    public function getByGroup(int $groupId): Collection
    {
        return $this->repository->getByGroup($groupId);
    }

    public function getByContactType(int $typeId): Collection
    {
        return $this->repository->getByContactType($typeId);
    }

    public function reorder(array $properties): void
    {
        foreach ($properties as $item) {
            CrmProperty::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
    }

    /**
     * Legt die Eigenschaft ans Ende ihrer Gruppe und ordnet sie Kontakttypen zu. Ohne ausdrückliche Auswahl
     * ($contactTypeIds === null) bekommen sie alle Typen, die die Gruppe bereits nutzen: sonst bliebe das neue
     * Feld auf jeder Kontaktseite unsichtbar, bis es pro Typ einzeln angehakt wird.
     *
     * @param array<string, mixed> $data
     * @param array<int, int>|null $contactTypeIds
     */
    public function store(array $data, ?array $contactTypeIds = null): CrmProperty
    {
        return DB::transaction(function () use ($data, $contactTypeIds): CrmProperty {
            $groupId = (int) $data['crm_property_group_id'];
            $data['sort_order'] ??= (int) CrmProperty::query()
                ->where('crm_property_group_id', $groupId)
                ->max('sort_order') + 1;

            $property = $this->repository->create($data);

            $this->attachToContactTypes(
                $property,
                $contactTypeIds ?? $this->contactTypeIdsUsingGroup($groupId)
            );

            return $property;
        });
    }

    /**
     * Gleicht die Typ-Zuordnung einer Eigenschaft ab. Bestehende Zuordnungen behalten ihre Optionen
     * (Pflichtfeld, Listenspalte, Filter); Systemeigenschaften bleiben an Systemtypen hängen.
     *
     * @param array<int, int> $contactTypeIds
     */
    public function syncContactTypes(CrmProperty $property, array $contactTypeIds): void
    {
        $wanted = array_values(array_unique(array_map('intval', $contactTypeIds)));
        $current = $property->contactTypes()->get();

        $removable = $current
            ->reject(fn (CrmContactType $type): bool => in_array($type->id, $wanted, true))
            ->reject(fn (CrmContactType $type): bool => $property->is_system && $type->is_system)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($property, $wanted, $current, $removable): void {
            if ($removable !== []) {
                $property->contactTypes()->detach($removable);
            }

            $this->attachToContactTypes($property, array_diff($wanted, $current->pluck('id')->all()));
        });
    }

    /**
     * @return array<int, int>
     */
    private function contactTypeIdsUsingGroup(int $groupId): array
    {
        return DB::table('crm_contact_type_property')
            ->join('crm_properties', 'crm_properties.id', '=', 'crm_contact_type_property.crm_property_id')
            ->where('crm_properties.crm_property_group_id', $groupId)
            ->distinct()
            ->pluck('crm_contact_type_property.crm_contact_type_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Die Pivot-Sortierung bestimmt die Gruppenreihenfolge des Typs: Die Eigenschaft reiht sich deshalb bei
     * ihrer Gruppe ein; nutzt der Typ die Gruppe noch nicht, kommt sie ans Ende.
     *
     * @param array<int, int> $contactTypeIds
     */
    private function attachToContactTypes(CrmProperty $property, array $contactTypeIds): void
    {
        foreach (array_unique(array_map('intval', $contactTypeIds)) as $contactTypeId) {
            $pivot = DB::table('crm_contact_type_property')->where('crm_contact_type_id', $contactTypeId);
            $groupPosition = (clone $pivot)
                ->join('crm_properties', 'crm_properties.id', '=', 'crm_contact_type_property.crm_property_id')
                ->where('crm_properties.crm_property_group_id', $property->crm_property_group_id)
                ->max('crm_contact_type_property.sort_order');

            $property->contactTypes()->syncWithoutDetaching([
                $contactTypeId => [
                    'sort_order' => $groupPosition ?? ((int) $pivot->max('sort_order') + 1),
                ],
            ]);
        }
    }

    public function update(CrmProperty $property, array $data): bool
    {
        // Systemeigenschaften werden im Code über Name/Typ gefunden (E-Mail-Duplikate, Tooltip)
        $renamesOrRetypes = (isset($data['name']) && $data['name'] !== $property->name)
            || (isset($data['type']) && $data['type'] !== $property->type?->value);
        if ($property->is_system && $renamesOrRetypes) {
            throw new CrmSettingLockedException(__('System properties cannot be renamed or changed in type.'));
        }

        $newType = isset($data['type']) ? CrmPropertyTypeEnum::tryFrom((string) $data['type']) : null;
        $convertsToMultiselect = $newType === CrmPropertyTypeEnum::MULTISELECT
            && $property->type !== CrmPropertyTypeEnum::MULTISELECT;
        if ($newType !== null && $newType !== $property->type) {
            $this->assertTypeChangeKeepsValues($property, $newType);
        }

        return DB::transaction(function () use ($property, $data, $convertsToMultiselect): bool {
            $updated = $this->repository->update($property, $data);

            if ($convertsToMultiselect) {
                // Einfachauswahl → Mehrfachauswahl: jeder Wert wird zur Liste mit einem Eintrag. Bewusst ohne
                // Abgleich mit den Optionen: im selben Speichern umbenannte Optionen oder Altwerte, die nie eine
                // Option waren, gingen sonst still verloren (zurückwandeln ist gesperrt).
                $property->values()->each(function (CrmPropertyValue $value): void {
                    $current = trim((string) $value->value);
                    $value->update(['value' => $current === '' ? null : CrmPropertyValueFormat::encode([$current])]);
                });
            }

            return $updated;
        });
    }

    /**
     * Mehrfachauswahl nur aus einer Einfachauswahl heraus (Werte bleiben gültig) und nicht zurück, damit
     * keine gewählten Optionen verloren gehen.
     *
     * @throws ValidationException
     */
    private function assertTypeChangeKeepsValues(CrmProperty $property, CrmPropertyTypeEnum $newType): void
    {
        $leavesMultiselect = $property->type === CrmPropertyTypeEnum::MULTISELECT;
        $entersMultiselectFromOther = $newType === CrmPropertyTypeEnum::MULTISELECT
            && $property->type !== CrmPropertyTypeEnum::SELECT;

        // Datei-Eigenschaften speichern Pfade: als Text stünden sie offen im Feld und die Dateien fielen aus
        // jedem Aufräumen; umgekehrt würden Texte als Dateipfad gelesen.
        $touchesUpload = in_array(CrmPropertyTypeEnum::UPLOAD, [$property->type, $newType], true);
        $hasValues = $property->values()->whereNotNull('value')->where('value', '!=', '')->exists();
        if ($touchesUpload && $hasValues) {
            throw ValidationException::withMessages([
                'type' => __(
                    'The type of a file upload property can only be changed while no contact has a value.'
                ),
            ]);
        }

        if ($leavesMultiselect || $entersMultiselectFromOther) {
            throw ValidationException::withMessages([
                'type' => __('A multiple choice property can only be created from a single choice and cannot be changed back.'),
            ]);
        }
    }

    public function destroy(CrmProperty $property): void
    {
        if ($property->is_system) {
            throw new CrmSettingLockedException(__('System properties cannot be deleted.'));
        }

        // Die Werte verschwinden per FK-Cascade; ihre Dateien würden sonst ohne Verweis liegen bleiben.
        $files = $this->fileService->collectPathsOfProperties([(int) $property->id]);

        DB::transaction(function () use ($property, $files): void {
            $this->repository->delete($property);
            DB::afterCommit(fn () => $this->fileService->deleteMany($files));
        });
    }
}
