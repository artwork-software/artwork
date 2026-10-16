<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Exceptions\CrmSettingLockedException;
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

    public function store(array $data): CrmProperty
    {
        return $this->repository->create($data);
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
                // Einfachauswahl → Mehrfachauswahl: jeder Wert wird zur Liste mit einem Eintrag
                $property->refresh();
                $property->values()->each(function (CrmPropertyValue $value) use ($property): void {
                    $value->update(['value' => CrmPropertyValueFormat::toStorage($property, $value->value)]);
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

        $this->repository->delete($property);
    }

    public function assignToTypes(CrmProperty $property, array $typeIds): void
    {
        $property->contactTypes()->sync($typeIds);
    }
}
