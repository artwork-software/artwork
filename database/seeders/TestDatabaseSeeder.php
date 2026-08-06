<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            // must run before AuthUserSeeder, which resolves the "Mitarbeiter"/"Meister" qualifications
            ShiftQualificationSeeder::class,
            SettingsSeeder::class,
            AuthUserSeeder::class,
        ]);
    }
}
