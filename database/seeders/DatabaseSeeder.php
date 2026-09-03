<?php

namespace Database\Seeders;

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
            ProjectManagementBuilderSeed::class,
            InventoryArticlePropertiesSeeder::class,
            ManufacturerSeeder::class,
            InventoryArticleStatusSeeder::class,
            EventStatusSeeder::class,
            DatabaseSettingsSeeder::class,
            SubdivisionSeeder::class,
            ShiftQualificationIconsSeeder::class,
        ]);

        \Illuminate\Support\Facades\Artisan::call('artwork:add-bi-event-type-tags');
        \Illuminate\Support\Facades\Artisan::call('artwork:add-bi-export-presets');
    }
}
