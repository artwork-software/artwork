<?php

namespace Database\Seeders;

use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Artwork\Modules\PermissionPresets\Repositories\PermissionPresetRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Throwable;

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
     * @throws Throwable
     */
    public function run(): void
    {
        foreach ($this->permissionRepository->getPermissionsGroupedByPermissionGroup() as $permissionsByGroup) {
            /** @var Collection $permissionPresetData */
            $permissionPresetData = $permissionsByGroup->pluck('group', 'id');
            $this->permissionPresetRepository->saveOrFail(
                new PermissionPreset([
                    'name' => $permissionPresetData->first(),
                    'permissions' => $permissionPresetData->keys()
                ])
            );
        }
    }
}
