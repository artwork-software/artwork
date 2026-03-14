<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Repositories\CrmPropertyValueRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class CrmSyncService
{
    private static bool $syncing = false;

    public function __construct(
        private readonly CrmPropertyValueRepository $propertyValueRepository,
    ) {}

    public function syncFromModel(Model $model): void
    {
        if (self::$syncing) {
            return;
        }

        self::$syncing = true;

        try {
            $crmContact = $model->crmContact;

            if (!$crmContact) {
                return;
            }

            $mappings = $this->getFieldMappings($model);

            foreach ($mappings as $propertyName => $modelValue) {
                $property = $crmContact->contactType
                    ->properties()
                    ->where('name', $propertyName)
                    ->first();

                if ($property) {
                    $this->propertyValueRepository->updateOrCreate(
                        $crmContact->id,
                        $property->id,
                        (string) $modelValue
                    );
                }
            }

            $displayName = $this->getDisplayName($model);
            if ($displayName && $crmContact->display_name !== $displayName) {
                $crmContact->update(['display_name' => $displayName]);
            }
        } finally {
            self::$syncing = false;
        }
    }

    private function getFieldMappings(Model $model): array
    {
        if ($model instanceof User) {
            return [
                'Email' => $model->email,
                'Telefon' => $model->phone_number,
                'Position' => $model->position,
                'Business' => $model->business,
            ];
        }

        if ($model instanceof Freelancer) {
            return [
                'Email' => $model->email,
                'Telefon' => $model->phone_number,
                'Position' => $model->position,
                'Business' => $model->business,
                'Work Name' => $model->work_name,
                'Work Description' => $model->work_description,
                'Stundensatz' => $model->salary_per_hour,
                'Vergütungsbeschreibung' => $model->salary_description,
                'Can Work Shifts' => $model->can_work_shifts ? '1' : '0',
            ];
        }

        if ($model instanceof ServiceProvider) {
            return [
                'Email' => $model->email,
                'Telefon' => $model->phone_number,
                'Work Name' => $model->work_name,
                'Work Description' => $model->work_description,
                'Stundensatz' => $model->salary_per_hour,
                'Vergütungsbeschreibung' => $model->salary_description,
                'Can Work Shifts' => $model->can_work_shifts ? '1' : '0',
            ];
        }

        return [];
    }

    private function getDisplayName(Model $model): ?string
    {
        if ($model instanceof User) {
            return trim($model->first_name . ' ' . $model->last_name);
        }

        if ($model instanceof Freelancer) {
            return trim($model->first_name . ' ' . $model->last_name);
        }

        if ($model instanceof ServiceProvider) {
            return $model->provider_name;
        }

        return null;
    }
}
