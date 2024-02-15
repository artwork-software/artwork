<?php

namespace Database\Seeders;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Artwork\Modules\PermissionPresets\Repositories\PermissionPresetRepository;
use Illuminate\Database\Seeder;

class PermissionPresetSeeder extends Seeder
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly PermissionPresetRepository $permissionPresetRepository
    ) {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Standard User',
                'permissions' => [
                    $this->permissionRepository->getIdByName(PermissionNameEnum::PROJECT_VIEW->value),
                    $this->permissionRepository->getIdByName(PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value),
                    $this->permissionRepository->getIdByName(PermissionNameEnum::EVENT_REQUEST->value),
                    $this->permissionRepository->getIdByName(PermissionNameEnum::CONTRACT_SEE_DOWNLOAD->value)
                ]
            ])
        );

        $this->permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Vertrags- & Dokumentenadmin',
                'permissions' => [
                    $this->permissionRepository->getIdByName(PermissionNameEnum::CONTRACT_EDIT_UPLOAD->value),
                    $this->permissionRepository
                        ->getIdByName(PermissionNameEnum::PROJECT_BUDGET_SEE_DOCS_CONTRACTS->value)
                ]
            ])
        );

        $this->permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Budgetadmin',
                'permissions' => [
                    $this->permissionRepository->getIdByName(PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value),
                    $this->permissionRepository
                        ->getIdByName(PermissionNameEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value)
                ]
            ])
        );

        $this->permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Disponent*in',
                'permissions' => [
                    $this->permissionRepository->getIdByName(PermissionNameEnum::ROOM_UPDATE->value)
                ]
            ])
        );

        $this->permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Finanzierungsquellenadmin',
                'permissions' => [
                    $this->permissionRepository->getIdByName(PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value),
                    $this->permissionRepository->getIdByName(PermissionNameEnum::MONEY_SOURCE_EDIT_DELETE->value)
                ]
            ])
        );
    }
}
