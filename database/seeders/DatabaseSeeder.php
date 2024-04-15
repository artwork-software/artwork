<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
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
            DefaultComponentSeeder::class,
            ContentSeeder::class,
            CraftSeeder::class,
            ShiftQualificationSeeder::class,
            AuthUserSeeder::class,
            FreelancerSeeder::class,
            ServiceProviderSeeder::class,
            WalidRaadSeeder::class,
            PermissionPresetSeeder::class,
            ChangeEventTypeSvgToHexSeed::class,
        ]);
    }
}
