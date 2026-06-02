<?php

namespace Database\Seeders;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Befüllt Kalender und Schichtplan mit abwechslungsreichen Testdaten für eine Demo-/Testinstanz.
 *
 * - Legt das Areal "Testareal" mit mehreren Räumen an (idempotent).
 * - Schaltet vorhandene can_work_shifts-User für die genutzten Gewerke + Funktionen frei
 *   (craftables + shift_qualifiables), damit sie regelkonform zugewiesen werden können.
 * - Verteilt Events verschiedener Typen über den Zielmonat.
 * - Erstellt zu bespielbaren Events Schichten inkl. Bedarf (shifts_qualifications)
 *   UND tatsächlicher Zuweisung im Pivot shift_workers.
 *
 * Zielmonat konfigurierbar über ENV SEED_MONTH (Format Y-m), Default = aktueller Monat:
 *   SEED_MONTH=2026-07 php artisan db:seed --class=TestArealShiftSeeder
 *
 * Idempotent: zuvor von diesem Seeder erzeugte Events/Schichten desselben Monats in den
 * Testareal-Räumen werden vor dem Befüllen entfernt (Marker option_string = self::SEED_MARKER).
 */
class TestArealShiftSeeder extends Seeder
{
    private const SEED_MARKER = 'TESTAREAL_SEED';

    private const AREA_NAME = 'Testareal';

    /** @var array<int, array{name: string, capacity: int}> */
    private const ROOMS = [
        ['name' => 'Große Bühne', 'capacity' => 600],
        ['name' => 'Studiobühne', 'capacity' => 150],
        ['name' => 'Probebühne', 'capacity' => 80],
        ['name' => 'Foyer', 'capacity' => 300],
        ['name' => 'Werkstatt', 'capacity' => 40],
        ['name' => 'Konferenzraum', 'capacity' => 25],
    ];

    public function run(): void
    {
        $month = $this->resolveMonth();
        $this->command?->info(
            'Seeding Testareal-Daten für ' . $month->isoFormat('MMMM YYYY') .
            ' (' . $month->copy()->startOfMonth()->toDateString() . ' – ' . $month->copy()->endOfMonth()->toDateString() . ')'
        );

        $ownerId = $this->resolveOwnerId();
        $area = Area::firstOrCreate(['name' => self::AREA_NAME]);
        $rooms = $this->ensureRooms($area, $ownerId);

        $crafts = Craft::with('qualifications')->whereIn('id', [1, 2, 3])->get();
        if ($crafts->isEmpty()) {
            $crafts = Craft::with('qualifications')->get();
        }
        if ($crafts->isEmpty()) {
            $this->command?->warn('Keine Crafts vorhanden – breche ab. Bitte zuerst CraftSeeder ausführen.');
            return;
        }

        $userPool = $this->unlockUsersForCrafts($crafts);
        if ($userPool->isEmpty()) {
            $this->command?->warn('Keine can_work_shifts-User vorhanden – Schichten werden ohne Zuweisung erstellt.');
        }

        $this->cleanupPreviousSeed($rooms->pluck('id')->all(), $month);

        $eventTypes = $this->resolveEventTypes();
        $statusId = (int) (DB::table('event_statuses')->min('id') ?? 1);
        $projectIds = Project::query()->pluck('id')->all();

        [$eventCount, $shiftCount, $assignmentCount] = $this->seedMonth(
            $month,
            $rooms,
            $crafts,
            $userPool,
            $eventTypes,
            $statusId,
            $projectIds,
            $ownerId
        );

        $this->command?->info(
            "Fertig: {$eventCount} Events, {$shiftCount} Schichten, {$assignmentCount} Zuweisungen in " .
            $rooms->count() . ' Räumen.'
        );
    }

    private function resolveMonth(): Carbon
    {
        $raw = env('SEED_MONTH');
        if (is_string($raw) && trim($raw) !== '') {
            try {
                return Carbon::createFromFormat('Y-m', trim($raw))->startOfMonth();
            } catch (\Throwable) {
                $this->command?->warn("SEED_MONTH='{$raw}' ungültig (erwartet Y-m, z.B. 2026-07). Nutze aktuellen Monat.");
            }
        }

        return Carbon::now()->startOfMonth();
    }

    private function resolveOwnerId(): int
    {
        return (int) (User::query()->min('id') ?? 1);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Room>
     */
    private function ensureRooms(Area $area, int $ownerId)
    {
        $rooms = collect();
        foreach (self::ROOMS as $index => $config) {
            $rooms->push(
                Room::firstOrCreate(
                    ['name' => $config['name'], 'area_id' => $area->id],
                    [
                        'user_id' => $ownerId,
                        'order' => $index + 1,
                        'position' => $index + 1,
                        'everyone_can_book' => false,
                        'relevant_for_disposition' => true,
                        'capacity' => $config['capacity'],
                    ]
                )
            );
        }

        return $rooms;
    }

    /**
     * Schaltet möglichst viele vorhandene can_work_shifts-User für die genutzten Gewerke
     * und deren Funktionen frei (craftables + shift_qualifiables), idempotent.
     *
     * @param  \Illuminate\Support\Collection<int, Craft>  $crafts
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function unlockUsersForCrafts($crafts)
    {
        $users = User::query()->where('can_work_shifts', true)->get(['id']);
        if ($users->isEmpty()) {
            return $users;
        }

        $now = Carbon::now();
        $userType = User::class;

        foreach ($crafts as $craft) {
            $qualificationIds = $craft->qualifications->pluck('id')->all();

            foreach ($users as $user) {
                DB::table('craftables')->updateOrInsert(
                    [
                        'craft_id' => $craft->id,
                        'craftable_type' => $userType,
                        'craftable_id' => $user->id,
                    ],
                    ['updated_at' => $now, 'created_at' => $now]
                );

                foreach ($qualificationIds as $qualificationId) {
                    DB::table('shift_qualifiables')->updateOrInsert(
                        [
                            'shift_qualification_id' => $qualificationId,
                            'qualifiable_type' => $userType,
                            'qualifiable_id' => $user->id,
                            'craft_id' => $craft->id,
                        ],
                        ['updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }

        $this->command?->info(
            $users->count() . ' User für ' . $crafts->count() . ' Gewerke + Funktionen freigeschaltet.'
        );

        return User::query()->where('can_work_shifts', true)->get(['id', 'first_name', 'last_name']);
    }

    /**
     * @param  array<int, int>  $roomIds
     */
    private function cleanupPreviousSeed(array $roomIds, Carbon $month): void
    {
        if (empty($roomIds)) {
            return;
        }

        $eventIds = DB::table('events')
            ->where('option_string', self::SEED_MARKER)
            ->whereIn('room_id', $roomIds)
            ->whereBetween('start_time', [
                $month->copy()->startOfMonth()->startOfDay(),
                $month->copy()->endOfMonth()->endOfDay(),
            ])
            ->pluck('id')
            ->all();

        if (empty($eventIds)) {
            return;
        }

        $shiftIds = DB::table('shifts')->whereIn('event_id', $eventIds)->pluck('id')->all();

        if (!empty($shiftIds)) {
            DB::table('shift_workers')->whereIn('shift_id', $shiftIds)->delete();
            DB::table('shifts_qualifications')->whereIn('shift_id', $shiftIds)->delete();
            DB::table('shifts')->whereIn('id', $shiftIds)->delete();
        }

        DB::table('events')->whereIn('id', $eventIds)->delete();

        $this->command?->info('Vorheriger Testareal-Seed dieses Monats entfernt (' . count($eventIds) . ' Events).');
    }

    /**
     * @return array<string, int> name => id
     */
    private function resolveEventTypes(): array
    {
        return EventType::query()->pluck('id', 'name')->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Room>  $rooms
     * @param  \Illuminate\Support\Collection<int, Craft>  $crafts
     * @param  \Illuminate\Support\Collection<int, User>  $userPool
     * @param  array<string, int>  $eventTypes
     * @param  array<int, int>  $projectIds
     * @return array{0:int,1:int,2:int} [events, shifts, assignments]
     */
    private function seedMonth(
        Carbon $month,
        $rooms,
        $crafts,
        $userPool,
        array $eventTypes,
        int $statusId,
        array $projectIds,
        int $ownerId
    ): array {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $roomList = $rooms->values();
        $userIds = $userPool->pluck('id')->all();

        $eventCount = 0;
        $shiftCount = 0;
        $assignmentCount = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $isWeekend = $day->isWeekend();
            // Wochenenden voller, Werktage moderat -> ~40-60 Events über den Monat.
            $eventsToday = $isWeekend ? random_int(2, 4) : random_int(1, 2);

            // Räume des Tages durchmischen, damit nicht immer derselbe Raum zuerst belegt wird.
            $shuffledRooms = $roomList->shuffle();

            for ($i = 0; $i < $eventsToday; $i++) {
                $room = $shuffledRooms[$i % $shuffledRooms->count()];
                $plan = $this->pickEventPlan($eventTypes, $isWeekend);

                $event = $this->createEvent(
                    $day,
                    $room,
                    $plan,
                    $statusId,
                    $projectIds,
                    $ownerId
                );
                $eventCount++;

                if ($plan['shiftCrafts'] > 0) {
                    $craftsForEvent = $crafts->shuffle()->take(min($plan['shiftCrafts'], $crafts->count()));
                    foreach ($craftsForEvent as $craft) {
                        $created = $this->createShiftWithAssignments(
                            $event,
                            $room,
                            $craft,
                            $day,
                            $plan,
                            $userIds,
                            $projectIds
                        );
                        $shiftCount++;
                        $assignmentCount += $created;
                    }
                }
            }
        }

        return [$eventCount, $shiftCount, $assignmentCount];
    }

    /**
     * @param  array<string, int>  $eventTypes
     * @return array{type: string, eventTypeId: int, startHour: int, durationHours: int, allDay: bool, loud: bool, audience: int, shiftCrafts: int, demand: array<int, int>}
     */
    private function pickEventPlan(array $eventTypes, bool $isWeekend): array
    {
        // Gewichtete Auswahl an Event-Profilen für einen abwechslungsreichen Kalender.
        $profiles = [
            ['type' => 'Aufführung', 'startHour' => 19, 'duration' => 3, 'allDay' => false, 'loud' => true, 'audience' => true, 'shiftCrafts' => 2, 'weight' => $isWeekend ? 5 : 2],
            ['type' => 'Probe', 'startHour' => 14, 'duration' => 4, 'allDay' => false, 'loud' => true, 'audience' => false, 'shiftCrafts' => 1, 'weight' => 4],
            ['type' => 'Aufbau', 'startHour' => 8, 'duration' => 6, 'allDay' => false, 'loud' => false, 'audience' => false, 'shiftCrafts' => 2, 'weight' => 3],
            ['type' => 'Workshop', 'startHour' => 10, 'duration' => 5, 'allDay' => false, 'loud' => false, 'audience' => false, 'shiftCrafts' => 1, 'weight' => 2],
            ['type' => 'Meeting', 'startHour' => 11, 'duration' => 2, 'allDay' => false, 'loud' => false, 'audience' => false, 'shiftCrafts' => 0, 'weight' => 2],
            ['type' => 'Führung', 'startHour' => 16, 'duration' => 1, 'allDay' => false, 'loud' => false, 'audience' => true, 'shiftCrafts' => 0, 'weight' => 1],
            ['type' => 'Reinigung', 'startHour' => 7, 'duration' => 2, 'allDay' => false, 'loud' => false, 'audience' => false, 'shiftCrafts' => 0, 'weight' => 1],
        ];

        $bag = [];
        foreach ($profiles as $index => $profile) {
            for ($w = 0; $w < $profile['weight']; $w++) {
                $bag[] = $index;
            }
        }
        $chosen = $profiles[$bag[array_rand($bag)]];

        $eventTypeId = $eventTypes[$chosen['type']] ?? (int) (reset($eventTypes) ?: 1);

        return [
            'type' => $chosen['type'],
            'eventTypeId' => $eventTypeId,
            'startHour' => $chosen['startHour'],
            'durationHours' => $chosen['duration'],
            'allDay' => $chosen['allDay'],
            'loud' => $chosen['loud'],
            'audience' => $chosen['audience'] ? random_int(50, 400) : 0,
            'shiftCrafts' => $chosen['shiftCrafts'],
            'demand' => [],
        ];
    }

    /**
     * @param  array<int, int>  $projectIds
     * @param  array{type: string, eventTypeId: int, startHour: int, durationHours: int, allDay: bool, loud: bool, audience: int}  $plan
     */
    private function createEvent(
        Carbon $day,
        Room $room,
        array $plan,
        int $statusId,
        array $projectIds,
        int $ownerId
    ): Event {
        $startTime = $day->copy()->setTime($plan['startHour'], 0);
        $endTime = $startTime->copy()->addHours($plan['durationHours']);

        if ($plan['allDay']) {
            $startTime = $day->copy()->startOfDay();
            $endTime = $day->copy()->endOfDay();
        }

        $projectId = empty($projectIds) ? null : $projectIds[array_rand($projectIds)];
        $label = $plan['type'] . ' – ' . $room->name;

        return Event::create([
            'name' => $label,
            'eventName' => $label,
            'description' => 'Testdaten (' . self::SEED_MARKER . ')',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'occupancy_option' => false,
            'audience' => $plan['audience'] > 0,
            'is_loud' => $plan['loud'],
            'allDay' => $plan['allDay'],
            'event_type_id' => $plan['eventTypeId'],
            'room_id' => $room->id,
            'project_id' => $projectId,
            'user_id' => $ownerId,
            'is_series' => false,
            'accepted' => true,
            'is_planning' => false,
            'event_status_id' => $statusId,
            'option_string' => self::SEED_MARKER,
        ]);
    }

    /**
     * Erstellt eine Schicht inkl. Bedarf (shifts_qualifications) und weist regelkonform
     * freigeschaltete User über das Pivot shift_workers zu.
     *
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $projectIds
     * @param  array{type: string, startHour: int, durationHours: int}  $plan
     * @return int Anzahl tatsächlich erstellter Zuweisungen.
     */
    private function createShiftWithAssignments(
        Event $event,
        Room $room,
        Craft $craft,
        Carbon $day,
        array $plan,
        array $userIds,
        array $projectIds
    ): int {
        $start = $event->start_time instanceof Carbon ? $event->start_time : Carbon::parse($event->start_time);
        $end = $event->end_time instanceof Carbon ? $event->end_time : Carbon::parse($event->end_time);

        // Schicht etwas vor dem Event beginnen / danach enden lassen (Auf-/Abbau).
        $shiftStart = $start->copy()->subHour();
        $shiftEnd = $end->copy()->addHour();
        $dayString = $day->toDateString();

        $shift = Shift::create([
            'event_id' => $event->id,
            'craft_id' => $craft->id,
            'room_id' => $room->id,
            'project_id' => $event->project_id,
            'start_date' => $dayString,
            'end_date' => $dayString,
            'start' => $shiftStart->format('H:i:s'),
            'end' => $shiftEnd->format('H:i:s'),
            'break_minutes' => random_int(0, 45),
            'description' => $plan['type'] . ' – ' . $craft->name,
            'is_committed' => random_int(0, 100) < 60,
            'event_start_day' => $dayString,
            'event_end_day' => $dayString,
        ]);

        $qualificationIds = $craft->qualifications->pluck('id')->all();
        if (empty($qualificationIds)) {
            return 0;
        }

        $assignments = 0;
        $usedUserIds = [];

        foreach ($qualificationIds as $qualificationId) {
            // Soll-Bedarf je Funktion festlegen.
            $demand = random_int(1, 3);
            ShiftsQualifications::create([
                'shift_id' => $shift->id,
                'shift_qualification_id' => $qualificationId,
                'value' => $demand,
            ]);

            if (empty($userIds)) {
                continue;
            }

            // Besetzungsgrad variieren: voll, unterbesetzt oder (selten) leer.
            $roll = random_int(0, 100);
            if ($roll < 15) {
                $toAssign = 0;                      // offener Bedarf
            } elseif ($roll < 45) {
                $toAssign = max(0, $demand - random_int(1, $demand)); // unterbesetzt
            } else {
                $toAssign = $demand;                // voll besetzt
            }

            for ($a = 0; $a < $toAssign; $a++) {
                $userId = $this->pickUser($userIds, $usedUserIds);
                if ($userId === null) {
                    break;
                }
                $usedUserIds[] = $userId;

                $this->assignWorker($shift, $userId, $qualificationId, $craft->abbreviation, $dayString, $shiftStart, $shiftEnd);
                $assignments++;
            }
        }

        return $assignments;
    }

    /**
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $usedUserIds
     */
    private function pickUser(array $userIds, array $usedUserIds): ?int
    {
        $available = array_values(array_diff($userIds, $usedUserIds));
        if (empty($available)) {
            return null;
        }

        return $available[array_rand($available)];
    }

    /**
     * Befüllt das Pivot shift_workers analog ShiftWorkerRepository::createForShift.
     */
    private function assignWorker(
        Shift $shift,
        int $userId,
        int $qualificationId,
        ?string $craftAbbreviation,
        string $dayString,
        Carbon $shiftStart,
        Carbon $shiftEnd
    ): void {
        $now = Carbon::now();

        DB::table('shift_workers')->insert([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $userId,
            'shift_qualification_id' => $qualificationId,
            'shift_count' => 1,
            'craft_abbreviation' => $craftAbbreviation,
            'short_description' => null,
            'start_date' => $dayString,
            'end_date' => $dayString,
            'start_time' => $shiftStart->format('H:i:s'),
            'end_time' => $shiftEnd->format('H:i:s'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
