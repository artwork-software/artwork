<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        //Invitations
        Permission::create(['name' => 'invite users']);

        //Users
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'update users']);
        Permission::create(['name' => 'delete users']);

        //Departments
        Permission::create(['name' => 'create departments']);
        Permission::create(['name' => 'view departments']);
        Permission::create(['name' => 'update departments']);
        Permission::create(['name' => 'delete departments']);

        //Projects
        Permission::create(['name' => 'create projects']);
        Permission::create(['name' => 'update projects']);
        Permission::create(['name' => 'delete projects']);

        //Checklists
        Permission::create(['name' => 'create checklists']);
        Permission::create(['name' => 'view checklists']);
        Permission::create(['name' => 'update checklists']);
        Permission::create(['name' => 'delete checklists']);

        //Categories, Genres etc.
        Permission::create(['name' => 'create and edit categories_etc']);


        //Has every permission because of the gate in AuthServiceProvider
        Role::create(['name' => 'admin']);
    }
}
