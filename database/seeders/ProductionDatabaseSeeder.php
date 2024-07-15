<?php

namespace Database\Seeders;

use Database\Seeders\InventoryManagement\Production\InventoryManagementDefaultColumnsSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            PermissionPresetSeeder::class,
            ShiftQualificationSeeder::class,
            DefaultComponentSeeder::class,
            InventoryManagementDefaultColumnsSeeder::class
        ]);

        DB::table('event_types')->insert([
            'name' => 'Blocker',
            'hex_code' => '#A7A6B1',
            'project_mandatory' => false,
            'individual_name' => true,
            'abbreviation' => 'BL'
        ]);
    }
}
