<?php

namespace Database\Seeders;

use Database\Seeders\InventoryManagement\Production\InventoryManagementDefaultColumnsSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            PermissionPresetSeeder::class,
            ShiftQualificationSeeder::class,
            DefaultComponentSeeder::class,
            DefaultEventPropertiesSeeder::class,
            InventoryManagementDefaultColumnsSeeder::class,
            SubdivisionSeeder::class,
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
