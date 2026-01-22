<?php

namespace Database\Seeders;

use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Artwork\Modules\Setup\DataProvider\RoleAndPermissionDataProvider;
use Illuminate\Database\Seeder;
use Artwork\Modules\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /** @var RoleAndPermissionDataProvider $dataprovider */
        $dataprovider = app()->get(RoleAndPermissionDataProvider::class);

        foreach ($dataprovider->getRoles() as $roleData) {
            Role::firstOrCreate($roleData);
        }

        foreach ($dataprovider->getPermissions() as $permissionData) {
            Permission::firstOrCreate($permissionData);
        }
    }
}
