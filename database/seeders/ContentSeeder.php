<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\Contract;
use App\Models\ContractModule;
use App\Models\Department;
use App\Models\Project;
use App\Models\RoomCategory;
use Carbon\Carbon;
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
        $this->seedRoomCategories();
        $this->seedRoomAttributes();
        $this->seedContracts();
        $this->seedContractModules();
    }

    private function seedContracts()
    {

        Contract::create([
            'name' => 'Basic Vertrag',
            'basename' => 'basic_contract',
            'contract_partner' => 'Deutscher Staat',
            'amount' => 12000,
            'project_id' => 1,
            'description' => 'Das ist ein Vertrag und das hier ist der Kommentar dazu der irgendwann mal geschrieben wurde.
                                Er steht hier als Platzhalter.',
            'ksk_liable' => false,
            'resident_abroad' => true,
            'legal_form' => 'Werkvertrag',
            'type' => 'Sponsoring'
        ]);

        Contract::create([
            'name' => 'Advanced Vertrag',
            'basename' => 'advanced_contract',
            'contract_partner' => 'HAU',
            'amount' => 8000,
            'project_id' => 1,
            'description' => 'Das ist ein Vertrag und das hier ist der Kommentar dazu der irgendwann mal geschrieben wurde.
                                Er steht hier als Platzhalter.',
            'ksk_liable' => true,
            'resident_abroad' => false,
            'legal_form' => 'Werkvertrag',
            'type' => 'Collaboration'
        ]);


    }

    private function seedContractModules()
    {
        ContractModule::create([
            'name' => 'Baustein',
            'basename' => 'baustein',
        ]);

        ContractModule::create([
            'name' => 'Mittelteil',
            'basename' => 'mittelteil',
        ]);
    }

    private function seedDepartments()
    {
        $department = Department::create([
            'name' => 'Festivals Team',
            'svg_name' => 'icon_festival'
        ]);

        $tech_department = Department::create([
            'name' => 'Technik Team',
            'svg_name' => 'icon_technik_haus'
        ]);

        $tech_department->users()->attach(2);
        $department->users()->attach(1);
    }

    private function seedRoomCategories()
    {
        DB::table('room_categories')->insert([
            'name' => 'Meetingraum',
        ]);

        DB::table('room_categories')->insert([
            'name' => 'Foyer',
        ]);

        DB::table('room_categories')->insert([
            'name' => 'Büro',
        ]);
    }

    private function seedRoomAttributes()
    {
        DB::table('room_attributes')->insert([
            'name' => 'rollstuhlgerecht',
        ]);

        DB::table('room_attributes')->insert([
            'name' => 'groß',
        ]);

        DB::table('room_attributes')->insert([
            'name' => 'buchbar ohne Anfrage',
        ]);
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
            'name' => 'Blocker',
            'svg_name' => 'eventType0',
            'project_mandatory' => false,
            'individual_name' => true
        ]);

        DB::table('event_types')->insert([
            'name' => 'Meeting',
            'svg_name' => 'eventType1',
            'project_mandatory' => false,
            'individual_name' => true
        ]);

        DB::table('event_types')->insert([
            'name' => 'Workshop',
            'svg_name' => 'eventType2',
            'project_mandatory' => false,
            'individual_name' => true
        ]);

        DB::table('event_types')->insert([
            'name' => 'Aufführung',
            'svg_name' => 'eventType3',
            'project_mandatory' => false,
            'individual_name' => true
        ]);

        DB::table('events')->insert([
            'name' => 'Aufführung',
            'description' => null,
            'start_time' => Carbon::now()->addDay()->addHours(2),
            'end_time' => Carbon::now()->addDay()->addHours(3),
            'occupancy_option' => null,
            'audience' => null,
            'is_loud' => null,
            'event_type_id' => 1,
            'room_id' => 1,
            'project_id' => null,
            'user_id' => 1
        ]);

        DB::table('events')->insert([
            'name' => 'Meeting Rock & Wrestling',
            'description' => null,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'occupancy_option' => null,
            'audience' => null,
            'is_loud' => null,
            'event_type_id' => 1,
            'room_id' => 1,
            'project_id' => 1,
            'user_id' => 1
        ]);

        DB::table('events')->insert([
            'name' => 'Aufbau Rock & Wrestling',
            'description' => null,
            'start_time' => Carbon::now()->addDays(2)->subHours(2),
            'end_time' => Carbon::now()->addDays(2)->subHour(),
            'occupancy_option' => null,
            'audience' => null,
            'is_loud' => null,
            'event_type_id' => 1,
            'room_id' => 2,
            'project_id' => 1,
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
