<?php

namespace Database\Seeders;

use Database\Seeders\InventoryManagement\Dev\InventoryManagementDevSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BudgetColumnSettingSeeder::class,
            RolesAndPermissionsSeeder::class,
            ShiftQualificationSeeder::class,
            AuthUserSeeder::class,
            FreelancerSeeder::class,
            ServiceProviderSeeder::class,
            SettingsSeeder::class,
            DefaultComponentSeeder::class,
            DefaultEventPropertiesSeeder::class,
            ContentSeeder::class,
            CraftSeeder::class,
            WalidRaadSeeder::class,
            PermissionPresetSeeder::class,
            ChangeEventTypeSvgToHexSeed::class,
            InventoryManagementDevSeeder::class,
            ProjectManagementBuilderSeed::class,
            InventoryArticlePropertiesSeeder::class,
            ManufacturerSeeder::class,
            InventoryArticleStatusSeeder::class,
            EventStatusSeeder::class,
            DatabaseSettingsSeeder::class,
            SubdivisionSeeder::class,
            ShiftQualificationIconsSeeder::class,
        ]);
    }
}
