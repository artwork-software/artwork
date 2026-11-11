<?php

namespace Database\Seeders;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Support\LegalDefaultShiftRules;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lokale Demodaten fuer die KI-Schichtplanung (ai-shift-planning, E2E). **Nur `local`.**
 *
 * Idempotent: alle Termine, Schichten, Zuweisungen und Abwesenheiten mit dem Praefix „KI-Demo"
 * werden vor dem Anlegen entfernt (nur diese), Vertrag und Regeln per `firstOrCreate`.
 *
 * Inhalt (relativ zur laufenden ISO-Woche, drei Wochen):
 *  - Vertrag „KI-Demo Vertrag" mit den gesetzlichen Standardregeln Tagesmaximum (10 h),
 *    Wochenmaximum (48 h), Ruhezeit (11 h), Tage in Folge (6), Nachtarbeit (8 h) — als
 *    „KI-Demo …"-Regeln, zugewiesen an Tobias Paulick (1), Jannik Mueller (7), Elisabeth Knauf (13).
 *  - Freelancerin Sabine Krien (5) im Gewerk Ton.
 *  - je Woche vier Vorstellungstage (Di, Mi, Fr, Sa) mit Schichten Ton frueh 08–16, Ton spaet
 *    15:00–23:30, Ton Abbau 18–22, Licht 09–19 (10 h) und Licht Nacht 17–01; offene Plaetze je
 *    Funktion. Spaet + frueh am Folgetag reisst die Ruhezeit, frueh + Abbau das Tagesmaximum.
 *  - Ein voll besetzter Termin („KI-Demo Gastspiel (voll besetzt)") am Montag der zweiten Woche.
 *  - Bestehende Zuweisungen: Tobias (Ton frueh, erster Tag; Ton frueh Mi Woche 2),
 *    Jannik (Licht, erster Tag); Abwesenheit Elisabeth Do Woche 2.
 *  - ai-scenarios-3: „KI-Demo Budgetprojekt" mit Budget (Kosten < Erloese, eine auskommentierte
 *    Zeile, Tobias mit Budgetzugriff und Projektleitung), zwei Aufgaben fuer Tobias (eine
 *    ueberfaellig), eine Doppelbelegung morgen in Raum 6; Tobias im Team von Projekt 516.
 *  - ai-scenarios-4: zwei Projekte „KI-Demo Premiere Herbst" und „KI-Demo Festivalplanung" mit
 *    Budget-Deadline in diesem Monat (Tobias im Team, Leitung der Premiere), zwei Termine der
 *    Premiere am kommenden Freitag in Raum 6 mit je einer Ton-Schicht, in denen Jannik Mueller (7)
 *    eingeteilt ist (Ausfall ersetzen, Termine verschieben), ein Vertrag „KI-Demo Gastspielvertrag"
 *    mit Deadline in 10 Tagen.
 */
class AiShiftPlanningDemoSeeder extends Seeder
{
    public const PREFIX = 'KI-Demo';

    private const PROJECT_ID = 516;

    private const ROOM_ID = 5;

    private const EVENT_TYPE_ID = 7;

    private const CONFLICT_ROOM_ID = 6;

    private const CRAFT_TON = 5;

    private const CRAFT_LICHT = 7;

    private const Q_STAFF = 1;

    private const Q_MASTER = 2;

    private const USERS = [1, 7, 13];

    private const FREELANCER = 5;

    private const RULE_KEYS = ['dailyMax', 'weeklyMax', 'restTime', 'consecutiveDays', 'nightWorkMax'];

    /** @var list<string> */
    private array $summary = [];

    public function run(): void
    {
        if (!app()->environment('local')) {
            throw new RuntimeException('AiShiftPlanningDemoSeeder laeuft nur in der Umgebung "local".');
        }

        DB::transaction(function (): void {
            $this->cleanUp();
            $contract = $this->contractWithRules();
            $this->people($contract);
            $this->events();
            $this->scenarios();
            $this->scenariosFour();
        });

        foreach ($this->summary as $line) {
            $this->command?->line($line);
        }
    }

    /**
     * @return list<string>
     */
    public function summary(): array
    {
        return $this->summary;
    }

    private function cleanUp(): void
    {
        $eventIds = Event::withTrashed()->where('eventName', 'like', self::PREFIX . '%')->pluck('id');
        $shiftIds = Shift::withTrashed()->whereIn('event_id', $eventIds)->pluck('id');

        DB::table('shift_rule_violations')->whereIn('shift_id', $shiftIds)->delete();
        DB::table('shift_workers')->whereIn('shift_id', $shiftIds)->delete();
        DB::table('shifts_qualifications')->whereIn('shift_id', $shiftIds)->delete();
        DB::table('shifts')->whereIn('id', $shiftIds)->delete();
        DB::table('events')->whereIn('id', $eventIds)->delete();
        DB::table('vacations')->where('comment', 'like', self::PREFIX . '%')->delete();
        DB::table('tasks')->where('name', 'like', self::PREFIX . '%')->delete();
    }

    private function contractWithRules(): UserContract
    {
        /** @var UserContract $contract */
        $contract = UserContract::query()->firstOrCreate(
            ['name' => self::PREFIX . ' Vertrag'],
            [
                'free_full_days_per_week' => 1,
                'free_half_days_per_week' => 0,
                'special_day_rule_active' => true,
                'compensation_period' => 30,
                'description' => 'Demovertrag fuer die KI-Schichtplanung (lokal).',
                'free_sundays_per_season' => 0,
                'days_off_first_26_weeks' => 0,
            ]
        );

        foreach (LegalDefaultShiftRules::all() as $definition) {
            if (!in_array($definition['key'], self::RULE_KEYS, true)) {
                continue;
            }

            /** @var ShiftRule $rule */
            $rule = ShiftRule::query()->firstOrCreate(
                ['name' => self::PREFIX . ' ' . $definition['name']],
                array_merge(LegalDefaultShiftRules::attributesFor($definition), [
                    'name' => self::PREFIX . ' ' . $definition['name'],
                ])
            );
            $rule->contracts()->syncWithoutDetaching([$contract->id]);
            $this->summary[] = sprintf(
                'Regel #%d %s (%s = %s)',
                $rule->id,
                $rule->name,
                $rule->trigger_type,
                $rule->individual_number_value
            );
        }

        $this->summary[] = sprintf('Vertrag #%d %s', $contract->id, $contract->name);

        return $contract;
    }

    private function people(UserContract $contract): void
    {
        $setup = [
            1 => [[self::CRAFT_TON], [[self::Q_STAFF, self::CRAFT_TON], [self::Q_MASTER, self::CRAFT_TON]]],
            7 => [[self::CRAFT_TON, self::CRAFT_LICHT], [[self::Q_STAFF, null]]],
            13 => [[self::CRAFT_LICHT], [[self::Q_STAFF, self::CRAFT_LICHT]]],
        ];

        foreach (self::USERS as $userId) {
            /** @var User $user */
            $user = User::query()->findOrFail($userId);
            $user->forceFill(['can_work_shifts' => true])->save();
            [$crafts, $qualifications] = $setup[$userId];
            $user->assignedCrafts()->syncWithoutDetaching($crafts);
            $this->attachQualifications($user, $qualifications);

            $hasAssign = UserContractAssign::query()
                ->where('user_id', $userId)
                ->where('user_contract_id', $contract->id)
                ->exists();

            if (!$hasAssign && $user->activeWorkContract() === null) {
                UserContractAssign::query()->create([
                    'user_id' => $userId,
                    'user_contract_id' => $contract->id,
                    'valid_from' => CarbonImmutable::today()->subMonths(2)->toDateString(),
                    'valid_until' => null,
                    'free_full_days_per_week' => 1,
                    'free_half_days_per_week' => 0,
                    'special_day_rule_active' => true,
                    'compensation_period' => 30,
                    'overtime_rule_active' => false,
                    'free_sundays_per_season' => 0,
                    'days_off_first_26_weeks' => 0,
                ]);
            }

            $this->summary[] = sprintf(
                'Person user #%d %s — Vertrag: %s, Gewerke: %s',
                $user->id,
                $user->getFullNameAttribute(),
                $user->fresh()?->activeWorkContract()?->name ?? 'keiner',
                implode(', ', $user->assignedCrafts()->pluck('name')->all())
            );
        }

        /** @var Freelancer $freelancer */
        $freelancer = Freelancer::query()->findOrFail(self::FREELANCER);
        $freelancer->forceFill(['can_work_shifts' => true])->save();
        $freelancer->assignedCrafts()->syncWithoutDetaching([self::CRAFT_TON]);
        $this->attachQualifications($freelancer, [[self::Q_STAFF, self::CRAFT_TON]]);
        $this->summary[] = sprintf(
            'Person freelancer #%d %s — Gewerk Ton',
            $freelancer->id,
            $freelancer->getNameAttribute()
        );
    }

    /**
     * @param list<array{0: int, 1: int|null}> $qualifications
     */
    private function attachQualifications(User|Freelancer $person, array $qualifications): void
    {
        foreach ($qualifications as [$qualificationId, $craftId]) {
            $exists = DB::table('shift_qualifiables')
                ->where('qualifiable_type', $person->getMorphClass())
                ->where('qualifiable_id', $person->getKey())
                ->where('shift_qualification_id', $qualificationId)
                ->exists();

            if (!$exists) {
                $person->shiftQualifications()->attach($qualificationId, ['craft_id' => $craftId]);
            }
        }
    }

    private function events(): void
    {
        $today = CarbonImmutable::today();
        $monday = $today->startOfWeek(CarbonImmutable::MONDAY);
        $firstDay = null;

        for ($week = 0; $week < 3; $week++) {
            foreach ([1, 2, 4, 5] as $offset) { // Di, Mi, Fr, Sa
                $day = $monday->addWeeks($week)->addDays($offset);

                if ($day->lessThan($today)) {
                    continue;
                }

                $firstDay ??= $day;
                $this->performanceDay($day, $day->equalTo($firstDay), $week === 1 && $offset === 2);
            }
        }

        $this->fullyStaffedGuestPerformance($monday->addWeek());

        Vacation::query()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => 13,
            'date' => $monday->addWeek()->addDays(3)->toDateString(),
            'full_day' => true,
            'type' => VacationType::OFF_WORK->value,
            'comment' => self::PREFIX . ' Urlaub',
        ]);
        $this->summary[] = 'Abwesenheit: Elisabeth Knauf (13) Urlaub am '
            . $monday->addWeek()->addDays(3)->toDateString();
    }

    /**
     * ai-scenarios-3: Budgetprojekt (Kosten < Erloese, eine auskommentierte Zeile, Tobias mit
     * Budgetzugriff), zwei Aufgaben fuer Tobias (eine ueberfaellig), eine Doppelbelegung morgen im
     * Raum 6. Abwesenheit naechste Woche und offene Ton-Schichten kommen aus `events()`.
     */
    private function scenarios(): void
    {
        $today = CarbonImmutable::today();

        /** @var Project $project */
        $project = Project::query()->firstOrCreate(
            ['name' => self::PREFIX . ' Budgetprojekt'],
            ['budget_deadline' => $today->addMonth()->toDateString()]
        );
        $project->users()->syncWithoutDetaching([1 => ['access_budget' => true, 'is_manager' => true]]);
        Project::query()->find(self::PROJECT_ID)?->users()->syncWithoutDetaching([1]);

        if (!Table::query()->where('project_id', $project->id)->exists()) {
            $this->budgetTable($project);
        }

        /** @var Checklist $checklist */
        $checklist = Checklist::query()->firstOrCreate(
            ['name' => self::PREFIX . ' Aufgaben', 'user_id' => 1],
            ['project_id' => $project->id]
        );
        Task::query()->create([
            'name' => self::PREFIX . ' Technikplan an Gastspiel schicken',
            'description' => 'Ueberfaellige Demo-Aufgabe.',
            'deadline' => $today->subDays(2)->setTime(18, 0),
            'done' => false,
            'checklist_id' => $checklist->id,
            'order' => 1,
        ]);
        Task::query()->create([
            'name' => self::PREFIX . ' Mikrofone bestellen',
            'description' => 'Offene Demo-Aufgabe.',
            'deadline' => $today->addDays(3)->setTime(18, 0),
            'done' => false,
            'checklist_id' => $checklist->id,
            'order' => 2,
        ]);

        $tomorrow = $today->addDay();
        $bookings = [['Doppelbelegung Probe', '14:00', '17:00'], ['Doppelbelegung Fototermin', '16:00', '18:00']];
        foreach ($bookings as [$name, $start, $end]) {
            $label = self::PREFIX . ' ' . $name;
            $event = Event::query()->create([
                'name' => $label,
                'eventName' => $label,
                'start_time' => $tomorrow->toDateString() . ' ' . $start . ':00',
                'end_time' => $tomorrow->toDateString() . ' ' . $end . ':00',
                'occupancy_option' => false,
                'audience' => false,
                'is_loud' => false,
                'event_type_id' => self::EVENT_TYPE_ID,
                'project_id' => $project->id,
                'user_id' => 1,
                'room_id' => self::CONFLICT_ROOM_ID,
            ]);
            $this->summary[] = sprintf(
                'Doppelbelegung: Termin #%d %s %s %s–%s (Raum %d)',
                $event->id,
                $label,
                $tomorrow->toDateString(),
                $start,
                $end,
                self::CONFLICT_ROOM_ID
            );
        }

        $this->summary[] = sprintf(
            'Budgetprojekt #%d %s (Tobias mit Budgetzugriff), Checkliste #%d mit 2 Aufgaben (eine ueberfaellig)',
            $project->id,
            $project->name,
            $checklist->id
        );
    }

    /**
     * ai-scenarios-4: Projekte mit Budget-Deadline in diesem Monat (S18), zwei Termine desselben
     * Projekts am kommenden Freitag mit Schichten von Jannik (S9 Ausfall, S12 Verschieben), ein
     * Vertrag mit Deadline in 10 Tagen (S15).
     */
    private function scenariosFour(): void
    {
        $today = CarbonImmutable::today();
        $monthEnd = $today->endOfMonth()->startOfDay();
        $soon = $today->addDays(5)->min($monthEnd);
        $projects = [];

        foreach ([['Premiere Herbst', $soon], ['Festivalplanung', $monthEnd]] as [$name, $deadline]) {
            /** @var Project $project */
            $project = Project::query()->updateOrCreate(
                ['name' => self::PREFIX . ' ' . $name],
                ['budget_deadline' => $deadline->toDateString()]
            );
            $project->users()->syncWithoutDetaching([1 => ['is_manager' => $name === 'Premiere Herbst']]);
            $projects[] = $project;
            $this->summary[] = sprintf(
                'Projekt #%d %s, Budget-Deadline %s',
                $project->id,
                $project->name,
                $deadline->toDateString()
            );
        }

        [$premiere] = $projects;
        $friday = $today->next(CarbonImmutable::FRIDAY);
        $bookings = [
            ['Probe Premiere', '10:00', '13:00', '09:30', '13:00'],
            ['Pressetermin Premiere', '15:00', '16:00', '14:30', '16:30'],
        ];

        foreach ($bookings as [$name, $start, $end, $shiftStart, $shiftEnd]) {
            $event = $this->event($name, $friday, $start, $friday, $end, (int) $premiere->id, self::CONFLICT_ROOM_ID);
            $shift = $this->shift($event, self::CRAFT_TON, $friday, $shiftStart, $shiftEnd, 0, [self::Q_STAFF => 1]);
            $this->assign($shift, User::class, 7, self::Q_STAFF);
            $this->summary[] = sprintf(
                'Freitag: Termin #%d %s %s %s–%s (Raum %d) — Ton-Schicht #%d %s–%s mit Jannik Mueller (7)',
                $event->id,
                $event->eventName,
                $friday->toDateString(),
                $start,
                $end,
                self::CONFLICT_ROOM_ID,
                $shift->id,
                $shiftStart,
                $shiftEnd
            );
        }

        /** @var Contract $contract */
        $contract = Contract::query()->updateOrCreate(
            ['name' => self::PREFIX . ' Gastspielvertrag'],
            [
                'basename' => 'ki-demo-gastspielvertrag.pdf',
                'contract_partner' => 'Gastspielagentur Nord',
                'description' => 'Lokale Demodaten (AiShiftPlanningDemoSeeder).',
                'amount' => 4500,
                'creator_id' => 1,
                'project_id' => $premiere->id,
                'foreign_tax' => false,
                'deadline_date' => $today->addDays(10)->toDateString(),
            ]
        );
        $this->summary[] = sprintf(
            'Vertrag #%d %s, Deadline %s, Projekt #%d',
            $contract->id,
            $contract->name,
            $today->addDays(10)->toDateString(),
            $premiere->id
        );
    }

    /**
     * Drei Strukturspalten + Wertspalte „Plan": Kosten 12.000 (+ 800 auskommentiert), Erloese 15.500.
     */
    private function budgetTable(Project $project): void
    {
        $table = Table::query()->create(['project_id' => $project->id, 'name' => 'Budget', 'is_template' => false]);
        $columns = [];
        foreach (['KTO', 'KST', 'Position', 'Plan'] as $position => $name) {
            $columns[] = Column::query()->create([
                'table_id' => $table->id,
                'name' => $name,
                'subName' => $position === 3 ? 'Soll' : '',
                'type' => 'empty',
                'position' => $position,
                'relevant_for_project_groups' => $position === 3,
            ]);
        }

        $positions = [
            ['BUDGET_TYPE_COST', 'Personal', [['9000', false], ['800', true]]],
            ['BUDGET_TYPE_COST', 'Technik', [['3000', false]]],
            ['BUDGET_TYPE_EARNING', 'Kartenverkauf', [['12500', false]]],
            ['BUDGET_TYPE_EARNING', 'Foerderung', [['3000', false]]],
        ];
        foreach ($positions as $index => [$type, $name, $values]) {
            $main = MainPosition::query()->create([
                'table_id' => $table->id, 'type' => $type, 'name' => $name, 'position' => $index,
            ]);
            $sub = SubPosition::query()->create(['main_position_id' => $main->id, 'name' => $name, 'position' => 0]);
            foreach ($values as $order => [$value, $commented]) {
                $row = SubPositionRow::query()->create([
                    'sub_position_id' => $sub->id, 'commented' => $commented, 'position' => $order, 'order' => $order,
                ]);
                foreach ($columns as $column) {
                    ColumnCell::query()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $row->id,
                        'value' => $column->position === 3 ? $value : '',
                    ]);
                }
            }
        }
    }

    private function performanceDay(CarbonImmutable $day, bool $isFirstDay, bool $tobiasMidweek): void
    {
        $event = $this->event('Vorstellung', $day, '10:00', $day, '23:00');

        $tonEarly = $this->shift($event, self::CRAFT_TON, $day, '08:00', '16:00', 30, [self::Q_STAFF => 2]);
        $tonLate = $this->shift(
            $event,
            self::CRAFT_TON,
            $day,
            '15:00',
            '23:30',
            30,
            [self::Q_STAFF => 1, self::Q_MASTER => 1]
        );
        $this->shift($event, self::CRAFT_TON, $day, '18:00', '22:00', 0, [self::Q_STAFF => 1]);
        $licht = $this->shift($event, self::CRAFT_LICHT, $day, '09:00', '19:00', 45, [self::Q_STAFF => 1]);
        $this->shift($event, self::CRAFT_LICHT, $day, '17:00', '01:00', 30, [self::Q_STAFF => 1], $day->addDay());

        if ($isFirstDay) {
            $this->assign($tonEarly, User::class, 1, self::Q_STAFF);
            $this->assign($licht, User::class, 7, self::Q_STAFF);
        }

        if ($tobiasMidweek) {
            $this->assign($tonEarly, User::class, 1, self::Q_STAFF);
        }

        $this->summary[] = sprintf(
            'Termin #%d %s %s — Schichten Ton frueh #%d, Ton spaet #%d, Licht #%d …',
            $event->id,
            $event->eventName,
            $day->toDateString(),
            $tonEarly->id,
            $tonLate->id,
            $licht->id
        );
    }

    private function fullyStaffedGuestPerformance(CarbonImmutable $day): void
    {
        $event = $this->event('Gastspiel (voll besetzt)', $day, '12:00', $day, '22:00');
        $ton = $this->shift($event, self::CRAFT_TON, $day, '12:00', '20:00', 30, [self::Q_STAFF => 1]);
        $licht = $this->shift($event, self::CRAFT_LICHT, $day, '13:00', '21:00', 30, [self::Q_STAFF => 1]);
        $this->assign($ton, Freelancer::class, self::FREELANCER, self::Q_STAFF);
        $this->assign($licht, User::class, 13, self::Q_STAFF);

        $this->summary[] = sprintf(
            'Termin #%d %s %s — alle Schichten besetzt',
            $event->id,
            $event->eventName,
            $day->toDateString()
        );
    }

    private function event(
        string $name,
        CarbonImmutable $day,
        string $start,
        CarbonImmutable $endDay,
        string $end,
        int $projectId = self::PROJECT_ID,
        int $roomId = self::ROOM_ID
    ): Event {
        $label = self::PREFIX . ' ' . $name . ' ' . $day->format('d.m.');

        return Event::query()->create([
            'name' => $label,
            'eventName' => $label,
            'description' => 'Lokale Demodaten (AiShiftPlanningDemoSeeder).',
            'start_time' => $day->toDateString() . ' ' . $start . ':00',
            'end_time' => $endDay->toDateString() . ' ' . $end . ':00',
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'event_type_id' => self::EVENT_TYPE_ID,
            'project_id' => $projectId,
            'user_id' => 1,
            'room_id' => $roomId,
        ]);
    }

    /**
     * @param array<int, int> $demand Funktion => Plaetze
     */
    private function shift(
        Event $event,
        int $craftId,
        CarbonImmutable $day,
        string $start,
        string $end,
        int $breakMinutes,
        array $demand,
        ?CarbonImmutable $endDay = null
    ): Shift {
        /** @var Shift $shift */
        $shift = Shift::query()->create([
            'event_id' => $event->id,
            'craft_id' => $craftId,
            'start_date' => $day->toDateString(),
            'end_date' => ($endDay ?? $day)->toDateString(),
            'start' => $start,
            'end' => $end,
            'break_minutes' => $breakMinutes,
            'is_committed' => false,
            'event_start_day' => $day->toDateString(),
            'event_end_day' => ($endDay ?? $day)->toDateString(),
            'room_id' => $event->room_id,
            'project_id' => $event->project_id,
            'shift_uuid' => (string) Str::uuid(),
        ]);

        foreach ($demand as $qualificationId => $value) {
            ShiftsQualifications::query()->create([
                'shift_id' => $shift->id,
                'shift_qualification_id' => $qualificationId,
                'value' => $value,
            ]);
        }

        return $shift;
    }

    private function assign(Shift $shift, string $type, int $id, int $qualificationId): void
    {
        DB::table('shift_workers')->insert([
            'shift_id' => $shift->id,
            'employable_type' => $type,
            'employable_id' => $id,
            'shift_qualification_id' => $qualificationId,
            'is_overbooked' => false,
            'shift_count' => 1,
            'craft_abbreviation' => $shift->craft_id === self::CRAFT_TON ? 'T' : 'L',
            'start_date' => $shift->start_date?->toDateString(),
            'end_date' => $shift->end_date?->toDateString(),
            'start_time' => $shift->start,
            'end_time' => $shift->end,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
