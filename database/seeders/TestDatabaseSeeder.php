<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Category;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Room;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            TestContentSeeder::class,
            AuthUserSeeder::class,
        ]);
    }
}
