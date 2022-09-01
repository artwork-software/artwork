<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $project = Project::create([
            'name' => 'Hydrospektive - Rock & Wrestling',
            'description' => 'Die HYDROSPEKTIVE ist ein fluides Festival auf der Außenalster,
             das zu sieben Abenden mit Konzerten, Lesungen und Performances auf dem Wasser einlädt.
             Es handelt sich aber um keine ordinäre »Seebühne«, wie sie viele Provinztheater rund
              um den Globus mit stolz geschwellter Brust zelebrieren. Au contraire!',
            'number_of_participants' => null,
            'cost_center' => null,
            'sector_id' => null,
            'category_id' => 1,
            'genre_id' => 1
        ]);

        $project->project_histories()->create([
            "user_id" =>1,
            "description" => "Projekt angelegt"
        ]);

        Checklist::create([
            'name' => 'Aufbau',
            'project_id' => 1,
        ]);

        $second_project = Project::create([
            'name' => 'Hydrospektive - Fahim Amir',
            'description' => null,
            'number_of_participants' => null,
            'cost_center' => null,
            'sector_id' => null,
            'category_id' => null,
            'genre_id' => null
        ]);

        $second_project->project_histories()->create([
            "user_id" =>1,
            "description" => "Projekt angelegt"
        ]);
    }
}
