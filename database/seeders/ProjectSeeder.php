<?php

namespace Database\Seeders;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::factory(250)->create();
    }
}
