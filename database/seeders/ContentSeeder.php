<?php

namespace Database\Seeders;

use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Category\Services\CategoryService;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Genre\Services\GenreService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Sector\Services\SectorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentSeeder extends Seeder
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly GenreService $genreService,
        private readonly SectorService $sectorService
    ) {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->seedContractTypes();
        $this->seedCompanyTypes();
        $this->seedGenreAndCategoriesAndAreas();
        $this->seedDepartments();
        $this->seedRooms();
        $this->seedCostCenters();
        $this->seedProjects();
        $this->seedEventsAndEventTypes();
        $this->seedRoomCategories();
        $this->seedRoomAttributes();
        $this->seedCurrencies();
    }


    private function seedCurrencies(): void
    {
        $faker = \Faker\Factory::create();
        Currency::create([
            'name' => '€',
            'color' => $faker->hexColor
        ]);
        Currency::create([
            'name' => '$',
            'color' => $faker->hexColor
        ]);
        Currency::create([
            'name' => 'CHF',
            'color' => $faker->hexColor
        ]);
        Currency::create([
            'name' => '£',
            'color' => $faker->hexColor
        ]);
    }

    private function seedContractTypes(): void
    {
        $faker = \Faker\Factory::create();
        ContractType::create([
            'name' => 'Werkvertrag',
            'color' => $faker->hexColor
        ]);

        ContractType::create([
            'name' => 'Dienstvertrag',
            'color' => $faker->hexColor
        ]);
    }

    private function seedCompanyTypes(): void
    {
        $faker = \Faker\Factory::create();
        CompanyType::create([
            'name' => 'GmbH',
            'color' => $faker->hexColor
        ]);

        CompanyType::create([
            'name' => 'GbR',
            'color' => $faker->hexColor
        ]);

        CompanyType::create([
            'name' => 'AG',
            'color' => $faker->hexColor
        ]);
    }

    private function seedDepartments(): void
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

    private function seedRoomCategories(): void
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

    private function seedRoomAttributes(): void
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

    private function seedGenreAndCategoriesAndAreas(): void
    {
        DB::table('areas')->insert([
            'name' => 'Hauptareal',
        ]);
        DB::table('areas')->insert([
            'name' => 'Außenbereich',
        ]);

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 10; $i++) {
            $this->categoryService->create(collect([
                'name' => $faker->randomElement([
                    'Kunst',
                    'Musik',
                    'Theater',
                    'Literatur',
                    'Film',
                    'Tanz',
                    'Fotografie',
                    'Design',
                    'Mode',
                    'Architektur',
                    'Gastronomie',
                    'Kultur',
                    'Gesundheit',
                    'Sport',
                    'Technik',
                    'Wissenschaft',
                    'Politik',
                    'Geschichte',
                    'Natur',
                    'Reisen',
                    'Essen',
                    'Trinken',
                    'Kochen',
                    'Backen',
                    'Gärtnern',
                    'Handwerk',
                    'DIY',
                    'Kreativität',
                    'Nachhaltigkeit',
                    'Umwelt',
                    'Tiere',
                    'Menschen',
                    'Kinder',
                    'Jugendliche',
                    'Erwachsene',
                    'Senioren',
                    'Familie',
                    'Freunde',
                    'Liebe',
                    'Beziehung',
                    'Gesellschaft',
                    'Kommunikation',
                    'Medien',
                    'Internet',
                    'Technologie',
                    'Digitalisierung',
                    'Zukunft',
                    'Vergangenheit',
                    'Gegenwart',
                    'Zeit',
                    'Raum',
                    'Ort',
                    'Stadt',
                    'Land',
                    'Welt',
                    'Universum',
                    'Gesundheit',
                    'Krankheit',
                    'Heilung',
                    'Therapie',
                    'Medizin',
                    'Pflege',
                    'Ernährung',
                    'Fitness',
                    'Sport',
                    'Bewegung',
                    'Entspannung',
                    'Meditation',
                    'Achtsamkeit',
                    'Spiritualität',
                    'Religion',
                    'Philosophie',
                    'Psychologie',
                    'Kunsttherapie',
                    'Musiktherapie',
                    'Tanztherapie',
                    'Theatertherapie',
                    'Literaturtherapie',
                    'Kreativtherapie',
                    'Sozialtherapie',
                    'Pädagogik',
                    'Schule',
                    'Bildung',
                    'Ausbildung',
                ]),
                'color' => $faker->hexColor
            ]));
        }

        for ($i = 0; $i < 10; $i++) {
            $this->genreService->create(collect([
               'name' => $faker->randomElement([
                   'Pop',
                   'Rock',
                   'Klassik',
                   'Jazz',
                   'Blues',
                   'Soul',
                   'Funk',
                   'Reggae',
                   'Hip-Hop',
                   'Rap',
                   'Metal',
                   'Punk',
                   'Indie',
                   'Alternative',
                   'Country',
                   'Folk',
                   'Electro',
                   'Techno',
                   'House',
                   'Trance',
                   'Dubstep',
                   'Drum & Bass',
                   'Hardstyle',
                   'Schlager',
                   'Volksmusik',
                   'Chanson',
                   'Oper',
                   'Operette',
                   'Musical',
                   'Film',
                   'Kabarett',
                   'Comedy',
                   'Satire',
                   'Improvisation',
                   'Zauberei',
                   'Pantomime',
                   'Clownerie',
                   'Akrobatik',
                   'Jonglage',
                   'Feuershow',
                   'Tanztheater',
                   'Ballett',
                   'Contemporary',
                   'Modern',
                   'Streetdance',
                   'Breakdance',
                   'Hip-Hop',
                   'Jazzdance',
                   'Standard',
                   'Latein',
                   'Salsa',
                   'Tango',
                   'Walzer',
                   'Foxtrott',
                   'Cha-Cha-Cha',
                   'Rumba',
                   'Samba',
                   'Jive',
                   'Quickstep',
                   'Paso Doble',
                   'Wiener Walzer',
                   'Musical',
                   'Operette',
                   'Oper',
                   'Konzert',
                   'Oratorium',
                   'Kammermusik',
                   'Symphonie',
                   'Sinfonie',
                   'Kantate',
                   'Messe',
                   'Requiem',
                   'Sonate',
                   'Quartett',
                   'Trio',
                   'Duett',
                   'Solo',
                   'Arie',
                   'Ouvertüre',
                   'Intermezzo',
                   'Fuge',
                   'Variationen',
                   'Rhapsodie',
                   'Impromptu',
                   'Etüde',
                   'Präludium',
                   'Nocturne',
                   'Scherzo',
                   'Ballade',
                   'Capriccio',
                   'Fantasie',
               ]),
               'color' => $faker->hexColor
            ]));
        }

        for ($i = 0; $i < 5; $i++) {
            $this->sectorService->create(collect([
                'name' => $faker->randomElement([
                    'Vorgarten',
                    'Hinterhof',
                    'Keller',
                    'Dachboden',
                    'Wohnzimmer',
                    'Küche',
                    'Bad',
                    'Schlafzimmer',
                    'Kinderzimmer',
                    'Arbeitszimmer',
                    'Büro',
                    'Gästezimmer',
                    'Esszimmer',
                    'Wohnzimmer',
                    'Wintergarten',
                    'Terrasse',
                    'Balkon',
                    'Garten',
                    'Garage',
                ]),
                'color' => $faker->hexColor
            ]));
        }
    }

    private function seedRooms(): void
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
            'everyone_can_book' => false,
            'created_at' => now(),
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
            'everyone_can_book' => false,
            'created_at' => now(),
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
            'everyone_can_book' => false,
            'created_at' => now(),
        ]);
    }

    private function seedEventsAndEventTypes(): void
    {
        DB::table('event_types')->insert([
            'name' => 'Blocker',
            'hex_code' => '#A7A6B1',
            'project_mandatory' => false,
            'individual_name' => true,
            'abbreviation' => 'BL'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Meeting',
            'hex_code' => '#641A54',
            'project_mandatory' => false,
            'individual_name' => true,
            'abbreviation' => 'M'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Workshop',
            'hex_code' => '#641A54',
            'project_mandatory' => false,
            'individual_name' => true,
            'abbreviation' => 'WS'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Aufführung',
            'hex_code' => '#EB7A3D',
            'project_mandatory' => true,
            'individual_name' => true,
            'abbreviation' => 'A'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Probe',
            'hex_code' => '#F1B640',
            'project_mandatory' => true,
            'individual_name' => false,
            'abbreviation' => 'P'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Führung',
            'hex_code' => '#86C554',
            'project_mandatory' => true,
            'individual_name' => false,
            'abbreviation' => 'F'
        ]);

        DB::table('event_types')->insert([
            'name' => 'Reinigung',
            'hex_code' => '#2EAA63',
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
            'user_id' => 1,
            'earliest_start_datetime' => Carbon::now()->addDay()->addHours(2),
            'latest_end_datetime' => Carbon::now()->addDay()->addHours(3),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
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
            'user_id' => 1,
            'earliest_start_datetime' => Carbon::now()->addDay()->subHour(),
            'latest_end_datetime' => Carbon::now()->addDay()->addHour(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        DB::table('events')->insert([
            'name' => 'Aufbau Rock & Wrestling',
            'description' => null,
            'start_time' => Carbon::now()->addDays(2)->subHours(2),
            'end_time' => Carbon::now()->addDays(2)->subHour(),
            'event_type_id' => 2,
            'room_id' => 2,
            'project_id' => 1,
            'user_id' => 1,
            'earliest_start_datetime' => Carbon::now()->addDays(2)->subHours(2),
            'latest_end_datetime' => Carbon::now()->addDays(2)->subHour(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }

    private function seedProjects(): void
    {


        $project = Project::create([
            'name' => 'Dan Daw Creative Projects',
            'number_of_participants' => null,
            'cost_center_id' => 1
        ]);

        /** @var Component $shortDescriptionComponent */
        $shortDescriptionComponent = Component::query()
            ->where('name', 'Short Description')
            ->where('type', 'TextArea')
            ->first();

        $shortDescriptionComponent->projectValue()->create([
            'project_id' => $project->id,
            'data' => [
                'text' => 'Nachdem er sein Leben lang Inspiration für andere gewesen ist, ergreift Dan Daw, ' .
                    'preisgekrönter, in Großbritannien lebender Choreograf und Performer, endlich die Gelegenheit, ' .
                    'sich selbst zu inspirieren. Er macht sich die wunderschöne Unordnung zu eigen, die alles ' .
                    'ausmacht, was er ist. Dabei lässt Dan los, wer er ehemals war, und macht Platz für den, ' .
                    'der er sein will. Dan Daw und Performer wie Komplize Christopher Owen finden sich in einem' .
                    ' intimen Play-Abend wieder: Dan erobert die Macht zurück, indem er sich nach seinen' .
                    ' eigenen Bedingungen dominieren lässt. Nominiert für die National Dance Awards 2021' .
                    ' sowie den Achievement in Dance Award der UK Theatre Awards gibt THE DAN DAW SHOW in ' .
                    'der Regie von Mark Maughan einen Einblick in die glänzende und schweißtreibende' .
                    ' Zerrissenheit, mit Scham zu leben und gleichzeitig vor Stolz zu strotzen.'
            ]
        ]);

        /** @var BudgetService $budgetService */
        $budgetService = app()->get(BudgetService::class);
        $budgetService->generateBasicBudgetValues($project);

        Checklist::create([
            'name' => 'Aufbau',
            'project_id' => 1,
        ]);

        $second_project = Project::create([
            'name' => 'IN THE HEART OF ANOTHER COUNTRY',
            'number_of_participants' => null,
            'cost_center_id' => 1
        ]);

        /** @var Component $shortDescriptionComponent */
        $shortDescriptionComponent = Component::query()
            ->where('name', 'Short Description')
            ->where('type', 'TextArea')
            ->first();

        $shortDescriptionComponent->projectValue()->create([
            'project_id' => $second_project->id,
            'data' => [
                'text' => 'In the Heart of Another Country erkundet den Heimatbegriff als Gefühl der Sehnsucht ' .
                    'und Zugehörigkeit von Künstler*innen verschiedener Diasporagruppen. Die Ausstellung widmet sich ' .
                    'der Frage, in welcher Weise physische Bewegung – Mobilität über Ländergrenzen hinweg – die ' .
                    'Rahmenbedingungen des internationalen zeitgenössischen Kunstschaffens formten. Auf ihren ' .
                    'Migrationsrouten durchquerten die Künstler*innen Süd- und Westasien, Afrika und die  ' .
                    'Karibik. Diemeisten von ihnen leben heute über die ganze Welt verstreut und weit von den' .
                    ' Orten entfernt, zu denen sie sich ursprünglich zugehörig fühlten.'
            ]
        ]);

        $budgetService->generateBasicBudgetValues($second_project);

        $nextProject = Project::create([
            'name' => 'Participative Audio Lab',
            'number_of_participants' => null,
        ]);

        /** @var Component $shortDescriptionComponent */
        $shortDescriptionComponent = Component::query()
            ->where('name', 'Short Description')
            ->where('type', 'TextArea')
            ->first();

        $shortDescriptionComponent->projectValue()->create([
            'project_id' => $nextProject->id,
            'data' => [
                'text' => 'Das Participative Audio Lab (PAL) ist eine neu gegründete Initiative, die sich auf ' .
                    'kollektive kreative Prozesse und die Entwicklung von Open-Source-Tools konzentriert, die es ' .
                    'Künstler*innen ermöglichen sollen, eigene partizipative digitale Musikprojekte zu  ' .
                    'gestalten und zuverbreiten, ohne dass sie selbst über Programmierkenntnisse verfügen  ' .
                    'müssen. Die Initiative wurde vergangenes Jahr im Rahmen des Projekts  ' .
                    '“Prototyping Sonic Institutions” ins Leben gerufen,  ' .
                    'das von Black Swan und CTM Festival zur Festivalausgabe 2022 organisiert wurde.'
            ]
        ]);

        $budgetService->generateBasicBudgetValues($nextProject);

        $nextProject = Project::create([
            'name' => 'Mega Projekt 🚀',
            'number_of_participants' => null,
        ]);

        /** @var Component $shortDescriptionComponent */
        $shortDescriptionComponent = Component::query()
            ->where('name', 'Short Description')
            ->where('type', 'TextArea')
            ->first();

        $shortDescriptionComponent->projectValue()->create([
            'project_id' => $nextProject->id,
            'data' => [
                'text' => 'One morning, when Gregor Samsa woke from troubled dreams, he found himself transformed ' .
                    'in his bed into a horrible vermin. He lay on his armour-like back, and if he lifted his head a ' .
                    'little he could see his brown belly, slightly domed and divided by arches into ' .
                    'stiff sections. The bedding was hardly able to cover it and seemed ready to slide off' .
                    ' any moment. His many legs, pitifully thin compared with the size of the rest of him, ' .
                    'waved about helplessly as he looked. "What`s happened to me?" he thought. It wasn`t a' .
                    ' dream. His room, a proper human room although a little too small, lay peacefully ' .
                    'between its four familiar walls. A collection of textile samples lay spread out on ' .
                    'the table - Samsa was a travelling salesman - and above it there hung a picture that he ' .
                    'had recently cut out of an illustrated magazine and housed in a nice, gilded frame.'
            ]
        ]);

        $budgetService->generateBasicBudgetValues($nextProject);
    }

    private function seedCostCenters(): void
    {
        CostCenter::create([
            'name' => '123456',
        ]);
    }
}
