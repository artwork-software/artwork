<?php

namespace Database\Seeders;

use App\Enums\BudgetTypesEnum;
use App\Enums\TabComponentEnums;
use App\Models\EventType;
use App\Models\MoneySource;
use App\Models\SeriesEvents;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\BudgetColumnSetting\Services\BudgetColumnSettingService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectStates;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WalidRaadSeeder extends Seeder
{
    public function __construct(private readonly BudgetColumnSettingService $budgetColumnSettingService)
    {
    }

    public function run(): void
    {
        /**
         * Create Money Source for Walid Raad Project
         */
        $moneySourceSuperQuelle = MoneySource::create([
            'creator_id' => 1,
            'name' => 'Super Quelle',
            'amount' => 100000,
        ]);

        $moneySourceTolleQuelle = MoneySource::create([
            'creator_id' => 1,
            'name' => 'Tolle Quelle',
            'amount' => 100000,
        ]);

        /**
         * Attach users to Money Source
         */
        $moneySourceSuperQuelle->users()->attach(2, ['competent' => true, 'write_access' => true]);

        /**
         * Create Task for Money Source
         */
        $task = $moneySourceSuperQuelle->moneySourceTasks()->create([
            'name' => 'Verwendungsnachweise',
            'description' => 'cxvxcvxvc',
            'deadline' => Carbon::now()->addDays(10),
            'creator' => 1,
            'done' => false
        ]);

        $task->money_source_task_users()->attach(2);

        $project = Project::create([
            'name' => 'Walid Raad',
            'shift_description' => 'Wird blutig',
            'key_visual_path' => 'M8AUVkujRBdqQu9rbS2Gart.JPG',
            'state' => 4
        ]);

        /** @var Component $shortDescriptionComponent */
        $shortDescriptionComponent = Component::query()
            ->where('name', 'Short Description')
            ->where('type', 'TextArea')
            ->first();

        $shortDescriptionComponent->projectValue()->create([
            'project_id' => $project->id,
            'data' => [
                'text' => 'COTTON UNDER MY FEET: THE HAMBURG CHAPTER. Eine Performance-Tour durch die Sammlung ' .
                    'der Kunsthalle – und in die Abgründe der Kunst- und Finanzwelt, inklusive Engeln und Untoten.'
            ]
        ]);


        $firstChecklistTabWithChecklistComponentId = ProjectTab::query()
            ->where('name', 'Checklists')
            ->whereRelation('components.component', 'type', TabComponentEnums::CHECKLIST->value)
            ->first()
            ->id;
        /**
         * create project Checklist with tasks
         */
        $checkListBudgetErstellen = $project->checklists()->create([
            'name' => 'Budget erstellen',
            'tab_id' => $firstChecklistTabWithChecklistComponentId
        ]);

        $checkListMarketing = $project->checklists()->create([
            'name' => 'Marketing',
            'tab_id' => $firstChecklistTabWithChecklistComponentId
        ]);

        $checkListCatering = $project->checklists()->create([
            'name' => 'Catering',
            'tab_id' => $firstChecklistTabWithChecklistComponentId
        ]);

        $checkListEinlass = $project->checklists()->create([
            'name' => 'Einlass',
            'tab_id' => $firstChecklistTabWithChecklistComponentId
        ]);

        $checkListBudgetErstellenKopie = $project->checklists()->create([
            'name' => 'Budget erstellen (Kopie)',
            'tab_id' => $firstChecklistTabWithChecklistComponentId
        ]);

        $checklist1Task1 = $checkListBudgetErstellen->tasks()->create([
            'name' => 'Marketing berücksichtigen',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 1
        ]);

        $checklist1Task2 = $checkListBudgetErstellen->tasks()->create([
            'name' => 'mit der Verwaltung besprechen',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 2
        ]);

        $checklist1Task3 = $checkListBudgetErstellen->tasks()->create([
            'name' => 'Vertrag mit Anwalt klären',
            'description' => 'Ruf mal Herrn X an',
            'done' => true,
            'deadline' => Carbon::now()->subDays(10),
            'done_at' => Carbon::now(),
            'order' => 7,
            'user_id' => 1
        ]);

        $checklist1Task1->task_users()->attach(2);
        $checklist1Task2->task_users()->attach(2);
        $checklist1Task3->task_users()->attach(2);

        $checklist2Task1 = $checkListMarketing->tasks()->create([
            'name' => 'Plakate, Flyer, Social-Media-Kampagnen planen',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 1
        ]);

        $checklist2Task2 = $checkListMarketing->tasks()->create([
            'name' => 'Marketingstrategie erstellen',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 2
        ]);

        $checklist2Task1->task_users()->attach(2);
        $checklist2Task2->task_users()->attach(2);

        $checklist3Task1 = $checkListCatering->tasks()->create([
            'name' => 'Kostenvoranschlag',
            'done' => true,
            'done_at' => Carbon::now(),
            'deadline' => Carbon::now()->subDays(10),
            'order' => 1,
            'user_id' => 1
        ]);

        $checklist3Task2 = $checkListCatering->tasks()->create([
            'name' => 'Bestellen',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 2
        ]);

        $checklist3Task1->task_users()->attach(1);


        $checklist4Task1 = $checkListEinlass->tasks()->create([
            'name' => 'mal das Artwork erklären',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 1,
        ]);

        $checklist5Task1 = $checkListBudgetErstellenKopie->tasks()->create([
            'name' => 'Marketing berücksichtigen',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 1
        ]);

        $checklist5Task2 = $checkListBudgetErstellenKopie->tasks()->create([
            'name' => 'mit der Verwaltung besprechen',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 2
        ]);

        $checklist5Task3 = $checkListBudgetErstellenKopie->tasks()->create([
            'name' => 'Vertrag mit Anwalt klären',
            'description' => 'Ruf mal Herrn X an',
            'done' => false,
            'deadline' => Carbon::now()->subDays(10),
            'order' => 7,
        ]);

        $projects = Project::all();

        /**
         * Create Project States
         */
        ProjectStates::create(['name' => 'Optional', 'color' => 'stateColorDefault']);
        ProjectStates::create(['name' => 'Läuft aktuell', 'color' => 'stateColorDefault']);
        ProjectStates::create(['name' => 'Läuft', 'color' => 'stateColorDarkGreen']);
        ProjectStates::create(['name' => 'In Planung', 'color' => 'stateColorOrange']);
        ProjectStates::create(['name' => 'Läuft', 'color' => 'stateColorDarkGreen']);
        ProjectStates::create(['name' => 'Abgeschlossen', 'color' => 'stateColorBlue']);


        /**
         * Add Project Walid Raad to Money Source
         */
        $moneySourceSuperQuelle->projects()->attach($project->id);
        $moneySourceTolleQuelle->projects()->attach($project->id);

        $firstCommentsTabWithCommentsComponentId = ProjectTab::query()
            ->where('name', 'Comments')
            ->whereRelation('components.component', 'type', TabComponentEnums::COMMENT_TAB->value)
            ->first()
            ->id;

        $project->comments()->create([
            'text' => 'Artwork ist toll!',
            'user_id' => 1,
            'tab_id' => $firstCommentsTabWithCommentsComponentId
        ]);

        $project->comments()->create([
            'text' => 'Ich weiß, ich habe schon eine Benachrichtigung erhalten ;)',
            'user_id' => 2,
            'tab_id' => $firstCommentsTabWithCommentsComponentId
        ]);

        $project->comments()->create([
            'text' => 'ich habe dich in die Marketing-Checkliste aufgenommen ;)',
            'user_id' => 1,
            'tab_id' => $firstCommentsTabWithCommentsComponentId
        ]);

        $project->comments()->create([
            'text' => 'Ich freue mich :)',
            'user_id' => 2,
            'tab_id' => $firstCommentsTabWithCommentsComponentId
        ]);

        $project->comments()->create([
            'text' => 'Hi, das Projekt wird toll',
            'user_id' => 1,
            'tab_id' => $firstCommentsTabWithCommentsComponentId
        ]);


        EventType::create([
            'name' => 'Aufbau',
            'hex_code' => '#168FC3',
            'project_mandatory' => false,
            'individual_name' => false,
            'abbreviation' => 'A',
            'relevant_for_shift' => true
        ]);


        $project->shiftRelevantEventTypes()->attach([2, 8]);


        $eventMain = $project->events()->create([
            'eventName' => 'Workshop',
            'start_time' => Carbon::now()->subDays(10)->startOfDay(),
            'end_time' => Carbon::now()->subDays(10)->endOfDay(),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 3,
            'room_id' => 1,
            'user_id' => 1,
            'is_series' => true,
            'series_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        $serieEvent1 = $project->events()->create([
            'eventName' => 'Workshop',
            'start_time' => Carbon::now()->subDays(9)->startOfDay(),
            'end_time' => Carbon::now()->subDays(9)->endOfDay(),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 3,
            'room_id' => 1,
            'user_id' => 1,
            'is_series' => true,
            'series_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        $serieEvent2 = $project->events()->create([
            'eventName' => 'Workshop',
            'start_time' => Carbon::now()->subDays(8)->startOfDay(),
            'end_time' => Carbon::now()->subDays(8)->endOfDay(),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 3,
            'room_id' => 1,
            'user_id' => 1,
            'is_series' => true,
            'series_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        SeriesEvents::create([
            'frequency_id' => 1,
            'end_date' => Carbon::now()->subDays(7)->startOfDay(),
        ]);

        $project->events()->create([
            'eventName' => 'Meeting',
            'start_time' => Carbon::now()->subDays(10)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->subDays(10)->addHour()->format('Y-m-d H:i:s'),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 2,
            'room_id' => 3,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => false
        ]);

        $project->events()->create([
            'eventName' => 'Reinigung',
            'start_time' => Carbon::now()->subDays(7)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->subDays(7)->addHour()->format('Y-m-d H:i:s'),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 7,
            'room_id' => 2,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => false
        ]);

        $project->events()->create([
            'eventName' => 'Probe',
            'start_time' => Carbon::now()->addDays(0)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->addDays(10)->addHour()->format('Y-m-d H:i:s'),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 5,
            'room_id' => 2,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => false
        ]);

        $project->events()->create([
            'eventName' => 'Probe',
            'start_time' => Carbon::now()->subDays(4)->startOfDay(),
            'end_time' => Carbon::now()->subDays(4)->endOfDay(),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 5,
            'room_id' => 3,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        $project->events()->create([
            'eventName' => 'Probe',
            'start_time' => Carbon::now()->addDays(4)->startOfDay(),
            'end_time' => Carbon::now()->addDays(4)->endOfDay(),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 5,
            'room_id' => 3,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        $project->events()->create([
            'eventName' => 'Theater XY',
            'start_time' => Carbon::now()->addDays(10)->startOfDay(),
            'end_time' => Carbon::now()->addDays(10)->endOfDay(),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 8,
            'room_id' => 3,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        $project->events()->create([
            'eventName' => 'Probe',
            'start_time' => Carbon::now()->subDays(7)->startOfDay(),
            'end_time' => Carbon::now()->subDays(7)->endOfDay(),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 5,
            'room_id' => 4,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        /** @var Event $eventWithManyShifts */
        $eventWithManyShifts = $project->events()->create([
            'eventName' => 'Einrichtung',
            'start_time' => Carbon::now()->subDays(6),
            'end_time' => Carbon::now()->subDays(6)->addHours(5),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 8,
            'room_id' => 4,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => true
        ]);

        $project->events()->create([
            'eventName' => 'Premiere',
            'start_time' => Carbon::now()->endOfDay()->subHours(3),
            'end_time' => Carbon::now()->endOfDay()->subMinutes(59),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 4,
            'room_id' => 4,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => false
        ]);

        $project->events()->create([
            'eventName' => 'Reinigung',
            'start_time' => Carbon::now()->subDays(10)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->subDays(10)->addHour()->format('Y-m-d H:i:s'),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 7,
            'room_id' => 5,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => false
        ]);

        /** @var Event $eventWithShift */
        $eventWithShift = $project->events()->create([
            'eventName' => 'Meeting',
            'start_time' => Carbon::now()->subDays(10)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->subDays(10)->addHour()->format('Y-m-d H:i:s'),
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => 2,
            'room_id' => 5,
            'user_id' => 1,
            'accepted' => false,
            'option_string' => null,
            'allDay' => false
        ]);

        /** @var Shift $shift */
        $shift = $eventWithShift->shifts()->create([
            'start_date' =>  $eventWithShift->start_time->format('Y-m-d'),
            'end_date' => $eventWithShift->end_time->format('Y-m-d'),
            'start' => $eventWithShift->start_time->format('H:i:s'),
            'end' => $eventWithShift->end_time->format('H:i:s'),
            'break_minutes' => '11',
            'craft_id' => 1,
            'shift_uuid' => Str::uuid(),
            'event_start_day' => $eventWithShift->start_time->format('Y-m-d'),
            'event_end_day' => $eventWithShift->end_time->format('Y-m-d'),
        ]);

        $workerShiftQualification = ShiftQualification::where('name', 'Mitarbeiter')->get()->first();
        $masterShiftQualification = ShiftQualification::where('name', 'Meister')->get()->first();

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $workerShiftQualification->id,
                'value' => 2
            ]
        );

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $masterShiftQualification->id,
                'value' => 2
            ]
        );

        $shift = $eventWithManyShifts->shifts()->create([
            'start_date' =>  $eventWithManyShifts->start_time->format('Y-m-d'),
            'end_date' => $eventWithManyShifts->end_time->format('Y-m-d'),
            'start' => $eventWithManyShifts->start_time->format('H:i:s'),
            'end' => $eventWithManyShifts->end_time->format('H:i:s'),
            'break_minutes' => '5',
            'craft_id' => 1,
            'shift_uuid' => Str::uuid(),
            'event_start_day' => $eventWithManyShifts->start_time->format('Y-m-d'),
            'event_end_day' => $eventWithManyShifts->end_time->format('Y-m-d'),
        ]);

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $workerShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $masterShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $shift = $eventWithManyShifts->shifts()->create([
            'start_date' =>  $eventWithManyShifts->start_time->format('Y-m-d'),
            'end_date' => $eventWithManyShifts->end_time->format('Y-m-d'),
            'start' => $eventWithManyShifts->start_time->format('H:i:s'),
            'end' => $eventWithManyShifts->end_time->format('H:i:s'),
            'break_minutes' => '5',
            'craft_id' => 1,
            'shift_uuid' => Str::uuid(),
            'event_start_day' => $eventWithManyShifts->start_time->format('Y-m-d'),
            'event_end_day' => $eventWithManyShifts->end_time->format('Y-m-d'),
        ]);

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $workerShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $masterShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $shift = $eventWithManyShifts->shifts()->create([
            'start_date' =>  $eventWithManyShifts->start_time->format('Y-m-d'),
            'end_date' => $eventWithManyShifts->end_time->format('Y-m-d'),
            'start' => $eventWithManyShifts->start_time->format('H:i:s'),
            'end' => $eventWithManyShifts->end_time->format('H:i:s'),
            'break_minutes' => '60',
            'craft_id' => 1,
            'shift_uuid' => Str::uuid(),
            'event_start_day' => $eventWithManyShifts->start_time->format('Y-m-d'),
            'event_end_day' => $eventWithManyShifts->end_time->format('Y-m-d'),
        ]);

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $workerShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $masterShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $shift = $eventWithManyShifts->shifts()->create([
            'start_date' =>  $eventWithManyShifts->start_time->format('Y-m-d'),
            'end_date' => $eventWithManyShifts->end_time->format('Y-m-d'),
            'start' => $eventWithManyShifts->start_time->format('H:i:s'),
            'end' => $eventWithManyShifts->end_time->format('H:i:s'),
            'break_minutes' => '1',
            'craft_id' => 1,
            'shift_uuid' => Str::uuid(),
            'event_start_day' => $eventWithManyShifts->start_time->format('Y-m-d'),
            'event_end_day' => $eventWithManyShifts->end_time->format('Y-m-d'),
        ]);

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $workerShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $shift->shiftsQualifications()->create(
            [
                'shift_id' => $shift->id,
                'shift_qualification_id' => $masterShiftQualification->id,
                'value' => fake()->numberBetween(0, 3) ?: null
            ]
        );

        $eventWithManyShifts->shifts()->create([
            'start_date' =>  $eventWithManyShifts->start_time->format('Y-m-d'),
            'end_date' => $eventWithManyShifts->end_time->format('Y-m-d'),
            'start' => $eventWithManyShifts->start_time->format('H:i:s'),
            'end' => $eventWithManyShifts->end_time->format('H:i:s'),
            'break_minutes' => '2',
            'craft_id' => 1,
            'shift_uuid' => Str::uuid(),
            'event_start_day' => $eventWithManyShifts->start_time->format('Y-m-d'),
            'event_end_day' => $eventWithManyShifts->end_time->format('Y-m-d'),
        ]);

        $firstArea = Area::find(1);

        $firstArea->rooms()->create([
            'name' => 'Theater',
            'user_id' => 1,
            'order' => 3
        ]);

        $firstArea->rooms()->create([
            'name' => 'Raum 5C',
            'user_id' => 1,
            'order' => 4
        ]);

        $secondArea = Area::find(2);

        $secondArea->rooms()->create([
            'name' => 'Raum 7D',
            'user_id' => 1,
            'order' => 2
        ]);

        $lastArea = Area::create([
            'name' => 'Haus 1'
        ]);

        $lastArea->rooms()->create([
            'name' => 'Raum 3B',
            'user_id' => 1,
            'order' => 1
        ]);

        $lastArea->rooms()->create([
            'name' => '6a',
            'user_id' => 1,
            'order' => 2
        ]);


        /**
         * create project budget table
         */

        $table = $project->table()->create([
            'name' => $project->name . ' Budgettabelle'
        ]);

        $columns = $table->columns()->createMany([
            [
                'name' => $this->budgetColumnSettingService->getColumnNameByColumnPosition(0),
                'subName' => '',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => $this->budgetColumnSettingService->getColumnNameByColumnPosition(1),
                'subName' => '',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => $this->budgetColumnSettingService->getColumnNameByColumnPosition(2),
                'subName' => '',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => 'V1-03/23 (€)',
                'subName' => 'A',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null,
                'color' => 'darkGreenColumn'
            ],
            [
                'name' => 'V2-06/23 (€)',
                'subName' => 'B',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null,
                'color' => 'darkLightBlueColumn'
            ],
            [
                'name' => 'V3-09/23 (€)',
                'subName' => 'C',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null,
                'color' => 'redColumn'
            ]
        ]);

        //take last three columns and reset indices
        $lastThreeColumns = $columns->take(-3)->values();

        // COST TABLE
        $costMainPosition = $table->mainPositions()->create([
            'type' => BudgetTypesEnum::BUDGET_TYPE_COST,
            'name' => 'Gesamtkosten Produktion',
            'position' => $table->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_COST)->max('position') + 1
        ]);

        $costMainPosition->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costMainPosition->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costMainPosition->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $costSubPosition = $costMainPosition->subPositions()->create([
            'name' => 'Gagen',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $costSubPosition->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costSubPosition->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costSubPosition->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        /** @var SubPositionRow $row1 */
        $row1 = $costSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);
        // Zeile 1
        $row1->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $row1->id,
            'value' => '3005',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row1->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $row1->id,
            'value' => '50800',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row1->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $row1->id,
            'value' => 'Operator Surtitles',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row1->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $row1->id,
            'value' => '950,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row1->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $row1->id,
            'value' => '950,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row1->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $row1->id,
            'value' => '950,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        // Zeile 2
        $row2 = $costSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $row2->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $row2->id,
            'value' => '3005',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row2->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $row2->id,
            'value' => '50800',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row2->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $row2->id,
            'value' => 'Performer',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row2->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $row2->id,
            'value' => '1000,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row2->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $row2->id,
            'value' => '1200,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);
        $row2->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $row2->id,
            'value' => '1100,00',
            'linked_money_source_id' => 1,
            'linked_type' => 'COST',
            'verified_value' => ''
        ]);

        // Zeile 3
        $row3 = $costSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $row3->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $row3->id,
            'value' => '3005',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row3->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $row3->id,
            'value' => '50800',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row3->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $row3->id,
            'value' => 'Regie',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row3->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $row3->id,
            'value' => '850,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row3->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $row3->id,
            'value' => '800,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row3->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $row3->id,
            'value' => '800,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        // Zeile 4
        $row4 = $costSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $row4->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $row4->id,
            'value' => '3005',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row4->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $row4->id,
            'value' => '50800',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row4->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $row4->id,
            'value' => 'Künstler',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row4->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $row4->id,
            'value' => '1200,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row4->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $row4->id,
            'value' => '1200,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $row4->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $row4->id,
            'value' => '1200,00',
            'linked_money_source_id' => 2,
            'linked_type' => 'COST',
            'verified_value' => ''
        ]);

        // Sub Position 2
        $costSubPosition2 = $costMainPosition->subPositions()->create([
            'name' => 'Reisekosten',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $costSubPosition2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costSubPosition2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costSubPosition2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        // Zeile 1
        $costSubPositionRow2 = $costSubPosition2->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $costSubPositionRow2->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $costSubPositionRow2->id,
            'value' => '3060',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $costSubPositionRow2->id,
            'value' => '20700',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $costSubPositionRow2->id,
            'value' => 'Reisekosten',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $costSubPositionRow2->id,
            'value' => '2482,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $costSubPositionRow2->id,
            'value' => '2450,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $costSubPositionRow2->id,
            'value' => '2450,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPosition3 = $costMainPosition->subPositions()->create([
            'name' => 'Transporte / Zoll',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $costSubPosition3->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costSubPosition3->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costSubPosition3->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $costSubPositionRow3 = $costSubPosition3->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $costSubPositionRow3->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $costSubPositionRow3->id,
            'value' => '3030',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow3->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $costSubPositionRow3->id,
            'value' => '20500',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow3->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $costSubPositionRow3->id,
            'value' => 'Transport Set',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow3->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $costSubPositionRow3->id,
            'value' => '1500,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
        ]);

        $costSubPositionRow3->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $costSubPositionRow3->id,
            'value' => '1500,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow3->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $costSubPositionRow3->id,
            'value' => '1600,00',
            'linked_money_source_id' => 1,
            'linked_type' => 'COST',
            'verified_value' => ''
        ]);

        $costMainPosition2 = $table->mainPositions()->create([
            'type' => BudgetTypesEnum::BUDGET_TYPE_COST,
            'name' => 'Gesamtkosten Technik',
            'position' => $table->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_COST)->max('position') + 1,
            'is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_REQUESTED
        ]);

        $costMainPosition2->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costMainPosition2->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costMainPosition2->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $costMainPosition2->verified()->create([
            'requested_by' => 1,
            'requested' => 2
        ]);

        $costSubPositionMain2 = $costMainPosition2->subPositions()->create([
            'name' => 'Ton',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $costSubPositionMain2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costSubPositionMain2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costSubPositionMain2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $costSubPositionRow1Main2 = $costSubPositionMain2->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $costSubPositionRow1Main2->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $costSubPositionRow1Main2->id,
            'value' => '4050',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow1Main2->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $costSubPositionRow1Main2->id,
            'value' => '10900',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow1Main2->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $costSubPositionRow1Main2->id,
            'value' => 'Mikrophon',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow1Main2->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $costSubPositionRow1Main2->id,
            'value' => '550,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
        ]);

        $costSubPositionRow1Main2->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $costSubPositionRow1Main2->id,
            'value' => '550,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow1Main2->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $costSubPositionRow1Main2->id,
            'value' => '43545,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPosition2Main2 = $costMainPosition2->subPositions()->create([
            'name' => 'Licht',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $costSubPosition2Main2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costSubPosition2Main2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costSubPosition2Main2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $costSubPositionRow2Main2 = $costSubPosition2Main2->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $costSubPositionRow2Main2->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $costSubPositionRow2Main2->id,
            'value' => '4070',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2Main2->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $costSubPositionRow2Main2->id,
            'value' => '15700',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2Main2->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $costSubPositionRow2Main2->id,
            'value' => 'kauf projektoren',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2Main2->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $costSubPositionRow2Main2->id,
            'value' => '2000,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
        ]);

        $costSubPositionRow2Main2->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $costSubPositionRow2Main2->id,
            'value' => '2200,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow2Main2->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $costSubPositionRow2Main2->id,
            'value' => '97987,00',
            'linked_money_source_id' => null,
            'verified_value' => 2200
        ]);

        $costSubPosition3Main2 = $costMainPosition2->subPositions()->create([
            'name' => 'sonstige Ausgaben',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $costSubPosition3Main2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $costSubPosition3Main2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $costSubPosition3Main2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $costSubPositionRow3Main2 = $costSubPosition3Main2->subPositionRows()->create([
            'commented' => true,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $costSubPositionRow3Main2->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $costSubPositionRow3Main2->id,
            'value' => '4010',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow3Main2->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $costSubPositionRow3Main2->id,
            'value' => '10800',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow3Main2->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $costSubPositionRow3Main2->id,
            'value' => 'Lizenzen',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $costSubPositionRow3Main2->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $costSubPositionRow3Main2->id,
            'value' => '200,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
            'commented' => true
        ]);

        $costSubPositionRow3Main2->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $costSubPositionRow3Main2->id,
            'value' => '200,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
            'commented' => true
        ]);

        $costSubPositionRow3Main2->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $costSubPositionRow3Main2->id,
            'value' => '300,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
            'commented' => true
        ]);

        // EARNING TABLE
        $earningMainPosition = $table->mainPositions()->create([
            'type' => BudgetTypesEnum::BUDGET_TYPE_EARNING,
            'name' => 'Einnahmen mit Budgetposten',
            'position' => $table->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_EARNING)->max('position') + 1
        ]);

        $earningMainPosition->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $earningMainPosition->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $earningMainPosition->mainPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $earningSubPosition = $earningMainPosition->subPositions()->create([
            'name' => 'Erstattung Sachleistungen',
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $earningSubPosition->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $earningSubPosition->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $earningSubPosition->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $earningSubPositionRow = $earningSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $earningSubPosition->subPositionRows()->max('position') + 1
        ]);

        $earningSubPositionRow->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $earningSubPositionRow->id,
            'value' => '8055',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $earningSubPositionRow->id,
            'value' => '20800',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $earningSubPositionRow->id,
            'value' => 'Fördermittel',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $earningSubPositionRow->id,
            'value' => '5000,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
        ]);

        $earningSubPositionRow->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $earningSubPositionRow->id,
            'value' => '5000,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $earningSubPositionRow->id,
            'value' => '5000,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPosition2 = $earningMainPosition->subPositions()->create([
            'name' => 'Gastspieleinnahmen',
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $earningSubPosition2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(0)->id
        ]);

        $earningSubPosition2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(1)->id
        ]);

        $earningSubPosition2->subPositionSumDetails()->create([
            'column_id' => $lastThreeColumns->get(2)->id
        ]);

        $earningSubPositionRow2 = $earningSubPosition2->subPositionRows()->create([
            'commented' => false,
            'position' => $earningSubPosition->subPositionRows()->max('position') + 1

        ]);

        $earningSubPositionRow2->cells()->create([
            'column_id' => $columns[0]->id,
            'sub_position_row_id' => $earningSubPositionRow2->id,
            'value' => '8001',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow2->cells()->create([
            'column_id' => $columns[1]->id,
            'sub_position_row_id' => $earningSubPositionRow2->id,
            'value' => '20850',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow2->cells()->create([
            'column_id' => $columns[2]->id,
            'sub_position_row_id' => $earningSubPositionRow2->id,
            'value' => 'Eingaben',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow2->cells()->create([
            'column_id' => $columns[3]->id,
            'sub_position_row_id' => $earningSubPositionRow2->id,
            'value' => '5000,00',
            'linked_money_source_id' => null,
            'verified_value' => '',
        ]);

        $earningSubPositionRow2->cells()->create([
            'column_id' => $columns[4]->id,
            'sub_position_row_id' => $earningSubPositionRow2->id,
            'value' => '5000,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $earningSubPositionRow2->cells()->create([
            'column_id' => $columns[5]->id,
            'sub_position_row_id' => $earningSubPositionRow2->id,
            'value' => '5000,00',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        BudgetSumDetails::create([
            'column_id' => $lastThreeColumns->get(0)->id,
            'type' => 'COST'
        ]);

        BudgetSumDetails::create([
            'column_id' => $lastThreeColumns->get(1)->id,
            'type' => 'COST'
        ]);

        BudgetSumDetails::create([
            'column_id' => $lastThreeColumns->get(2)->id,
            'type' => 'COST'
        ]);

        BudgetSumDetails::create([
            'column_id' => $lastThreeColumns->get(0)->id,
            'type' => 'EARNING'
        ]);

        BudgetSumDetails::create([
            'column_id' => $lastThreeColumns->get(1)->id,
            'type' => 'EARNING'
        ]);

        BudgetSumDetails::create([
            'column_id' => $lastThreeColumns->get(2)->id,
            'type' => 'EARNING'
        ]);
    }
}
