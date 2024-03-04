<?php

namespace Database\Seeders;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Artwork\Modules\PermissionPresets\Repositories\PermissionPresetRepository;
use Illuminate\Database\Seeder;

class PermissionPresetSeeder extends Seeder
{
    public function __construct()
    {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $permissionRepository = app()->get(PermissionRepository::class);
        $permissionPresetRepository = app()->get(PermissionPresetRepository::class);

        $permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Standard User',
                'permissions' => [
                    $permissionRepository->getIdByName(PermissionNameEnum::PROJECT_VIEW->value),
                    $permissionRepository->getIdByName(PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value),
                    $permissionRepository->getIdByName(PermissionNameEnum::EVENT_REQUEST->value),
                    $permissionRepository->getIdByName(PermissionNameEnum::CONTRACT_SEE_DOWNLOAD->value)
                ]
            ])
        );

        $permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Vertrags- & Dokumentenadmin',
                'permissions' => [
                    $permissionRepository->getIdByName(PermissionNameEnum::CONTRACT_EDIT_UPLOAD->value),
                    $permissionRepository
                        ->getIdByName(PermissionNameEnum::PROJECT_BUDGET_SEE_DOCS_CONTRACTS->value)
                ]
            ])
        );

        $permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Budgetadmin',
                'permissions' => [
                    $permissionRepository->getIdByName(PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value),
                    $permissionRepository
                        ->getIdByName(PermissionNameEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value)
                ]
            ])
        );

        $permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Disponent*in',
                'permissions' => [
                    $permissionRepository->getIdByName(PermissionNameEnum::ROOM_UPDATE->value)
                ]
            ])
        );

        $permissionPresetRepository->save(
            new PermissionPreset([
                'name' => 'Finanzierungsquellenadmin',
                'permissions' => [
                    $permissionRepository->getIdByName(PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value),
                    $permissionRepository->getIdByName(PermissionNameEnum::MONEY_SOURCE_EDIT_DELETE->value)
                ]
            ])
        );
    }
}
