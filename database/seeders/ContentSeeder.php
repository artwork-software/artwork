<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedGenreAndCategoriesAndAreas();
        $this->seedDepartments();
        $this->seedRooms();
        $this->seedEventsAndEventTypes();
        $this->seedProjects();
    }

    private function seedDepartments()
    {
        $department = Department::create([
            'name' => 'Festivals Team',
            'svg_name' => 'icon_festival'
        ]);

        $department->users()->attach(1);
    }

    private function seedGenreAndCategoriesAndAreas()
    {
        DB::table('areas')->insert([
            'name' => 'Areal 1B',
        ]);
        DB::table('categories')->insert([
            'name' => 'Festivals'
        ]);
        DB::table('genres')->insert([
            'name' => 'Rock'
        ]);
    }

    private function seedRooms()
    {
        DB::table('rooms')->insert([
            'name' => 'Hauptraum',
            'description' => null,
            'temporary' => false,
            'start_date' => null,
            'end_date' => null,
            'area_id' => 1,
            'user_id' => 1,
            'order' => 1,
            'everyone_can_book' => false
        ]);

        DB::table('rooms')->insert([
            'name' => 'Raum 2B',
            'description' => null,
            'temporary' => false,
            'start_date' => null,
            'end_date' => null,
            'area_id' => 1,
            'user_id' => 1,
            'order' => 1,
            'everyone_can_book' => false
        ]);
    }

    private function seedEventsAndEventTypes()
    {
        DB::table('event_types')->insert([
            'name' => 'Undefinierter Termin',
            'svg_name' => 'eventType0',
            'project_mandatory' => false,
            'individual_name' => true
        ]);

        DB::table('events')->insert([
            'name' => 'TestEvent',
            'description' => null,
            'start_time' => '2022-05-29T14:00',
            'end_time' => '2022-05-30T16:00',
            'occupancy_option' => null,
            'audience' => null,
            'is_loud' => null,
            'event_type_id' => 1,
            'room_id' => 1,
            'project_id' => null,
            'user_id' => 1
        ]);
    }

    private function seedProjects()
    {
        $project = Project::create([
            'name' => 'Hydrospektive - Rock & Wrestling',
            'description' => 'Die HYDROSPEKTIVE ist ein fluides Festival auf der Außenalster,
             das zu sieben Abenden mit Konzerten, Lesungen und Performances auf dem Wasser einlädt.
             Es handelt sich aber um keine ordinäre »Seebühne«, wie sie viele Provinztheater rund
              um den Globus mit stolz geschwellter Brust zelebrieren. Au contraire!',
            'number_of_participants' => null,
            'cost_center' => null,
        ]);

        $project->project_histories()->create([
            "user_id" => 1,
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
        ]);

        $second_project->project_histories()->create([
            "user_id" => 1,
            "description" => "Projekt angelegt",
        ]);
    }
}
