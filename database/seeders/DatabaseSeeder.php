<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            EventTypeSeeder::class,
            UserSeeder::class,
            AreaSeeder::class,
            ProjectSeeder::class,
            RoomSeeder::class,
            EventSeeder::class,
            SettingsSeeder::class,
            DepartmentSeeder::class,
            GenreSeeder::class,
            CategorySeeder::class
        ]);
    }
}
