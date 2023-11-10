<?php

namespace Database\Seeders;

use App\Enums\BudgetTypesEnum;
use App\Http\Controllers\ProjectController;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\CollectingSociety;
use App\Models\CompanyType;
use App\Models\Contract;
use App\Models\ContractModule;
use App\Models\ContractType;
use App\Models\Copyright;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\Department;
use App\Models\MoneySource;
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
        $this->seedCollectingSocieties();
        $this->seedContractTypes();
        $this->seedCompanyTypes();
        $this->seedGenreAndCategoriesAndAreas();
        $this->seedDepartments();
        $this->seedRooms();
        $this->seedEventsAndEventTypes();
        $this->seedProjects();
        $this->seedRoomCategories();
        $this->seedRoomAttributes();
        $this->seedCostCenters();
        $this->seedCopyrights();
        $this->seedCurrencies();
    }

    private function seedCollectingSocieties()
    {
        CollectingSociety::create([
            'name' => 'GEMA'
        ]);

        CollectingSociety::create([
            'name' => 'PETA'
        ]);

        CollectingSociety::create([
            'name' => 'KENA'
        ]);
    }

    private function seedCurrencies()
    {
        Currency::create([
            'name' => '€'
        ]);
        Currency::create([
            'name' => '$'
        ]);
        Currency::create([
            'name' => 'CHF'
        ]);
        Currency::create([
            'name' => '£'
        ]);
    }

    private function seedContractTypes()
    {
        ContractType::create([
            'name' => 'Werkvertrag'
        ]);

        ContractType::create([
            'name' => 'Dienstvertrag'
        ]);
    }

    private function seedCompanyTypes()
    {
        CompanyType::create([
            'name' => 'GmbH'
        ]);

        CompanyType::create([
            'name' => 'GbR'
        ]);

        CompanyType::create([
            'name' => 'AG'
        ]);
    }

    private function seedDepartments()
    {
        $department = Department::create([
            'name' => 'Kulturelle Bildung',
            'svg_name' => 'icon_bildung_kulturell'
        ]);

        $security_department = Department::create([
            'name' => 'Sicherheit',
            'svg_name' => 'icon_dienst_sicherheit'
        ]);

        $money = Department::create([
            'name' => 'Finanzen',
            'svg_name' => 'icon_orga_finanzen'
        ]);

        $stage = Department::create([
            'name' => 'Bühne',
            'svg_name' => 'icon_technik_buehne'
        ]);

        $management = Department::create([
            'name' => 'Gebäudemanagement',
            'svg_name' => 'icon_vermietung'
        ]);

        $free = Department::create([
            'name' => 'AG Barrierefreiheit',
            'svg_name' => 'icon_einhorn'
        ]);

        $security_department->users()->attach(2);
        $department->users()->attach(1);
        $stage->users()->attach(2);
        $free->users()->attach([1, 2]);

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

        DB::table('room_categories')->insert([
            'name' => 'Lager',
        ]);

        DB::table('room_categories')->insert([
            'name' => 'Workshop',
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
            'name' => 'Hauptareal',
        ]);
        DB::table('areas')->insert([
            'name' => 'Außenbereich',
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

        DB::table('rooms')->insert([
            'name' => 'Waldbühne',
            'description' => null,
            'temporary' => false,
            'start_date' => null,
            'end_date' => null,
            'area_id' => 2,
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
            'individual_name' => true,
            'abbreviation' => 'BL'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Meeting',
            'svg_name' => 'eventType1',
            'project_mandatory' => false,
            'individual_name' => true,
            'abbreviation' => 'M'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Workshop',
            'svg_name' => 'eventType2',
            'project_mandatory' => false,
            'individual_name' => true,
            'abbreviation' => 'WS'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Aufführung',
            'svg_name' => 'eventType3',
            'project_mandatory' => true,
            'individual_name' => true,
            'abbreviation' => 'A'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Probe',
            'svg_name' => 'eventType4',
            'project_mandatory' => true,
            'individual_name' => false,
            'abbreviation' => 'P'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Führung',
            'svg_name' => 'eventType5',
            'project_mandatory' => true,
            'individual_name' => false,
            'abbreviation' => 'F'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Reinigung',
            'svg_name' => 'eventType6',
            'project_mandatory' => false,
            'individual_name' => false,
            'abbreviation' => 'R'
        ]);

        DB::table('events')->insert([
            'name' => 'Aufführung',
            'eventName' => 'Aufführung',
            'description' => null,
            'start_time' => Carbon::now()->addDay()->addHours(2),
            'end_time' => Carbon::now()->addDay()->addHours(3),
            'event_type_id' => 1,
            'room_id' => 1,
            'project_id' => null,
            'user_id' => 1
        ]);

        DB::table('events')->insert([
            'name' => 'Meeting Rock & Wrestling',
            'eventName' => 'Meeting Rock & Wrestling',
            'description' => null,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
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
            'event_type_id' => 2,
            'room_id' => 2,
            'project_id' => 1,
            'user_id' => 1
        ]);
    }

    private function seedProjects()
    {
        $project = Project::create([
            'name' => 'Dan Daw Creative Projects',
            'description' => 'Nachdem er sein Leben lang Inspiration für andere gewesen ist, ergreift Dan Daw, preisgekrönter,
            in Großbritannien lebender Choreograf und Performer, endlich die Gelegenheit, sich selbst zu inspirieren.
            Er macht sich die wunderschöne Unordnung zu eigen, die alles ausmacht, was er ist. Dabei lässt Dan los, wer er ehemals war,
            und macht Platz für den, der er sein will. Dan Daw und Performer wie Komplize Christopher Owen finden sich in einem intimen
            Play-Abend wieder: Dan erobert die Macht zurück, indem er sich nach seinen eigenen Bedingungen dominieren lässt.
            Nominiert für die National Dance Awards 2021 sowie den Achievement in Dance Award der UK Theatre Awards gibt THE DAN DAW SHOW
            in der Regie von Mark Maughan einen Einblick in die glänzende und schweißtreibende Zerrissenheit, mit Scham zu leben und gleichzeitig vor Stolz zu strotzen.',
            'number_of_participants' => null,
        ]);

        $projectController = new ProjectController();
        $projectController->generateBasicBudgetValues($project);


        $project->project_histories()->create([
            "user_id" => 1,
            "description" => "Projekt angelegt"
        ]);

        Checklist::create([
            'name' => 'Aufbau',
            'project_id' => 1,
        ]);

        $second_project = Project::create([
            'name' => 'IN THE HEART OF ANOTHER COUNTRY',
            'description' => 'In the Heart of Another Country erkundet den Heimatbegriff als Gefühl der Sehnsucht und Zugehörigkeit von Künstler*innen verschiedener Diasporagruppen.
            Die Ausstellung widmet sich der Frage, in welcher Weise physische Bewegung – Mobilität über Ländergrenzen hinweg – die Rahmenbedingungen des internationalen zeitgenössischen Kunstschaffens formten.
            Auf ihren Migrationsrouten durchquerten die Künstler*innen Süd- und Westasien, Afrika und die Karibik.
            Die meisten von ihnen leben heute über die ganze Welt verstreut und weit von den Orten entfernt, zu denen sie sich ursprünglich zugehörig fühlten.',
            'number_of_participants' => null,
        ]);

        $second_project->project_histories()->create([
            "user_id" => 1,
            "description" => "Projekt angelegt",
        ]);

        $projectController->generateBasicBudgetValues($second_project);

        $nextProject = Project::create([
            'name' => 'Participative Audio Lab',
            'description' => 'Das Participative Audio Lab (PAL) ist eine neu gegründete Initiative, die sich auf kollektive kreative Prozesse und die Entwicklung von Open-Source-Tools konzentriert,
            die es Künstler*innen ermöglichen sollen, eigene partizipative digitale Musikprojekte zu gestalten und zu verbreiten, ohne dass sie selbst über Programmierkenntnisse verfügen müssen.
            Die Initiative wurde vergangenes Jahr im Rahmen des Projekts “Prototyping Sonic Institutions” ins Leben gerufen, das von Black Swan und CTM Festival zur Festivalausgabe 2022 organisiert wurde.',
            'number_of_participants' => null,
        ]);

        $nextProject->project_histories()->create([
            "user_id" => 1,
            "description" => "Projekt angelegt",
        ]);

        $projectController->generateBasicBudgetValues($nextProject);

        $nextProject = Project::create([
            'name' => 'Mega Projekt 🚀',
            'description' => 'One morning, when Gregor Samsa woke from troubled dreams, he found himself transformed in his bed into a horrible vermin.
            He lay on his armour-like back, and if he lifted his head a little he could see his brown belly, slightly domed and divided by arches into stiff sections.
            The bedding was hardly able to cover it and seemed ready to slide off any moment. His many legs, pitifully thin compared with the size of the rest of him,
            waved about helplessly as he looked. "What`s happened to me?" he thought. It wasn`t a dream. His room, a proper human room although a little too small,
            lay peacefully between its four familiar walls. A collection of textile samples lay spread out on the table - Samsa was a travelling salesman -
            and above it there hung a picture that he had recently cut out of an illustrated magazine and housed in a nice, gilded frame.',
            'number_of_participants' => null,
        ]);

        $nextProject->project_histories()->create([
            "user_id" => 1,
            "description" => "Projekt angelegt",
        ]);

        $projectController->generateBasicBudgetValues($nextProject);
    }

    private function seedCostCenters()
    {
        CostCenter::create([
            'name' => '123456',
            'description' => 'Some description',
            'project_id' => 1
        ]);
    }

    private function seedCopyrights()
    {
        Copyright::create([
            'own_copyright' => true,
            'live_music' => true,
            'collecting_society_id' => 1,
            'law_size' => 'small',
            'project_id' => 1
        ]);
    }

}
