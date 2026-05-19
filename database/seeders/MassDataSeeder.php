<?php

namespace Database\Seeders;

use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MassDataSeeder extends Seeder
{
    private const CRAFT_IDS = [1, 2, 3];
    private const QUALIFICATION_IDS = [1, 2, 3];
    private const EVENT_STATUS_ID = 1;
    private const CREATOR_USER_ID = 1;

    // Valid qualifications per craft (from craft_shift_qualification table)
    private const CRAFT_QUALIFICATIONS = [
        1 => [1, 2],    // Craft 1: Mitarbeiter, Meister
        2 => [1, 2],    // Craft 2: Mitarbeiter, Meister
        3 => [1, 2, 3], // Craft 3: Mitarbeiter, Meister, Rigging
    ];

    // EventType distribution weights (ID => weight)
    private const EVENT_TYPE_WEIGHTS = [
        8 => 30, // Aufbau
        5 => 25, // Probe
        4 => 20, // Aufführung
        2 => 10, // Meeting
        3 => 10, // Workshop
        7 => 5,  // Reinigung
    ];

    // Shift time templates [start, end, label]
    private const SHIFT_TEMPLATES = [
        ['08:00:00', '14:00:00', 'Früh'],
        ['14:00:00', '22:00:00', 'Spät'],
        ['18:00:00', '23:00:00', 'Abend'],
    ];

    private const PROJECT_NAMES = [
        'Sommerfestival 2026',
        'Winterproduktion',
        'Tanztheater Herbst',
        'Internationale Gastspiele',
        'Kinder- und Jugendprogramm',
        'Experimentelle Bühne',
        'Musiktheater Premiere',
        'Performance-Reihe',
        'Residenzprogramm',
        'Open Air Saison',
    ];

    private const PROJECT_COLORS = [
        '#A855F7', '#3B82F6', '#EF4444', '#10B981', '#F59E0B',
        '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#8B5CF6',
    ];

    private array $weightedEventTypes = [];

    public function run(): void
    {
        $this->buildWeightedEventTypes();

        $now = Carbon::now()->toDateTimeString();

        // 1. Create 150 users with can_work_shifts
        $this->command->info('Creating 150 users...');
        $users = User::factory()
            ->count(150)
            ->create([
                'can_work_shifts' => true,
                'weekly_working_hours' => 40,
            ]);
        $userIds = $users->pluck('id')->toArray();

        // 2. Assign 1-2 crafts per user (craftables = worker assignment)
        $this->command->info('Assigning crafts and qualifications to users...');
        $craftableRows = [];
        $qualifiableRows = [];
        $craftUsers = [];
        foreach (self::CRAFT_IDS as $craftId) {
            $craftUsers[$craftId] = [];
        }

        foreach ($userIds as $userId) {
            $craftCount = random_int(1, 2);
            $crafts = (array) array_rand(array_flip(self::CRAFT_IDS), $craftCount);
            foreach ($crafts as $craftId) {
                $craftableRows[] = [
                    'craft_id' => $craftId,
                    'craftable_type' => 'Artwork\\Modules\\User\\Models\\User',
                    'craftable_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $craftUsers[$craftId][] = $userId;

                // Assign at least 1 qualification within this craft
                $availableQuals = self::CRAFT_QUALIFICATIONS[$craftId];
                $qualCount = random_int(1, count($availableQuals));
                $assignedQuals = (array) array_rand(array_flip($availableQuals), $qualCount);
                foreach ($assignedQuals as $qualId) {
                    $qualifiableRows[] = [
                        'shift_qualification_id' => $qualId,
                        'qualifiable_id' => $userId,
                        'qualifiable_type' => 'Artwork\\Modules\\User\\Models\\User',
                        'craft_id' => $craftId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($craftableRows, 500) as $chunk) {
            DB::table('craftables')->insert($chunk);
        }
        foreach (array_chunk($qualifiableRows, 500) as $chunk) {
            DB::table('shift_qualifiables')->insert($chunk);
        }

        // 3. Create 10 projects
        $this->command->info('Creating 10 projects...');
        $projectIds = [];
        foreach (self::PROJECT_NAMES as $i => $name) {
            $projectIds[] = DB::table('projects')->insertGetId([
                'name' => $name,
                'color' => self::PROJECT_COLORS[$i],
                'artists' => '',
                'number_of_participants' => (string) random_int(20, 300),
                'user_id' => self::CREATOR_USER_ID,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. Get room IDs
        $roomIds = DB::table('rooms')->pluck('id')->toArray();
        if (empty($roomIds)) {
            $this->command->error('No rooms found. Please seed rooms first.');
            return;
        }

        // 5. Generate events, shifts, qualifications, workers
        $this->command->info('Generating events and shifts over 6 months...');

        $startDate = Carbon::parse('2026-05-19');
        $endDate = Carbon::parse('2026-11-18');

        $eventRows = [];
        $shiftRows = [];
        $qualificationRows = [];
        $workerRows = [];

        $eventCounter = 0;
        $shiftCounter = 0;

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayOfWeek = $currentDate->dayOfWeek; // 0=Sun, 1=Mon, 2=Tue...6=Sat
            $isWeekday = $dayOfWeek >= 2 && $dayOfWeek <= 6; // Tue-Sat

            foreach ($roomIds as $roomId) {
                $eventCount = $isWeekday ? random_int(1, 3) : random_int(0, 1);

                for ($e = 0; $e < $eventCount; $e++) {
                    $eventTypeId = $this->randomEventType();
                    $projectId = $projectIds[array_rand($projectIds)];
                    $isAllDay = random_int(1, 10) === 1; // ~10%

                    if ($isAllDay) {
                        $eventStart = $currentDate->copy()->setTime(0, 0);
                        $eventEnd = $currentDate->copy()->setTime(23, 59);
                    } else {
                        $startHour = random_int(8, 18);
                        $duration = random_int(2, 6);
                        $eventStart = $currentDate->copy()->setTime($startHour, 0);
                        $eventEnd = $currentDate->copy()->setTime(min($startHour + $duration, 23), 0);
                    }

                    $eventName = $this->eventNameForType($eventTypeId);

                    $eventRows[] = [
                        'name' => $eventName,
                        'eventName' => $eventName,
                        'description' => null,
                        'start_time' => $eventStart->toDateTimeString(),
                        'end_time' => $eventEnd->toDateTimeString(),
                        'earliest_start_datetime' => $eventStart->toDateTimeString(),
                        'latest_end_datetime' => $eventEnd->toDateTimeString(),
                        'occupancy_option' => 0,
                        'audience' => $eventTypeId === 4 ? 1 : 0, // Aufführung has audience
                        'is_loud' => in_array($eventTypeId, [4, 5]) ? 1 : 0,
                        'allDay' => $isAllDay ? 1 : 0,
                        'event_type_id' => $eventTypeId,
                        'room_id' => $roomId,
                        'user_id' => self::CREATOR_USER_ID,
                        'project_id' => $projectId,
                        'is_series' => 0,
                        'accepted' => 1,
                        'is_planning' => 0,
                        'event_status_id' => self::EVENT_STATUS_ID,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $eventCounter++;

                    // Generate 1-3 shifts per event
                    $shiftCount = random_int(1, 3);
                    $usedTemplates = (array) array_rand(self::SHIFT_TEMPLATES, min($shiftCount, 3));

                    foreach ($usedTemplates as $templateIdx) {
                        $template = self::SHIFT_TEMPLATES[$templateIdx];
                        $craftId = self::CRAFT_IDS[array_rand(self::CRAFT_IDS)];
                        $qualId = self::QUALIFICATION_IDS[array_rand(self::QUALIFICATION_IDS)];
                        $requiredWorkers = random_int(2, 5);

                        $shiftRows[] = [
                            'start_date' => $currentDate->toDateString(),
                            'end_date' => $currentDate->toDateString(),
                            'start' => $template[0],
                            'end' => $template[1],
                            'break_minutes' => 30,
                            'craft_id' => $craftId,
                            'description' => $template[2] . 'schicht',
                            'is_committed' => 0,
                            'shift_uuid' => Str::uuid()->toString(),
                            'event_start_day' => $currentDate->toDateString(),
                            'event_end_day' => $currentDate->toDateString(),
                            'created_at' => $now,
                            'updated_at' => $now,
                            '_meta_event_index' => $eventCounter - 1,
                            '_meta_craft_id' => $craftId,
                            '_meta_qual_id' => $qualId,
                            '_meta_required_workers' => $requiredWorkers,
                            '_meta_project_id' => $projectId,
                            '_meta_room_id' => $roomId,
                        ];
                        $shiftCounter++;
                    }
                }
            }

            $currentDate->addDay();
        }

        // 6. Bulk insert events and retrieve actual IDs
        $this->command->info("Inserting {$eventCounter} events...");
        $eventChunks = array_chunk($eventRows, 500);
        $eventIds = [];
        foreach ($eventChunks as $chunk) {
            DB::table('events')->insert($chunk);
            // MySQL lastInsertId returns the first auto-generated ID of the batch
            $firstIdInChunk = (int) DB::getPdo()->lastInsertId();
            for ($j = 0; $j < count($chunk); $j++) {
                $eventIds[] = $firstIdInChunk + $j;
            }
        }

        // 7. Assign event_id, room_id, project_id to shifts and bulk insert
        $this->command->info("Inserting {$shiftCounter} shifts...");
        $cleanShiftRows = [];
        foreach ($shiftRows as $row) {
            $eventIndex = $row['_meta_event_index'];
            $cleanShiftRows[] = [
                'event_id' => null,
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'start' => $row['start'],
                'end' => $row['end'],
                'break_minutes' => $row['break_minutes'],
                'craft_id' => $row['craft_id'],
                'description' => $row['description'],
                'is_committed' => $row['is_committed'],
                'shift_uuid' => $row['shift_uuid'],
                'event_start_day' => $row['event_start_day'],
                'event_end_day' => $row['event_end_day'],
                'room_id' => $row['_meta_room_id'],
                'project_id' => $row['_meta_project_id'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        $shiftChunks = array_chunk($cleanShiftRows, 500);
        $shiftIds = [];
        foreach ($shiftChunks as $chunk) {
            DB::table('shifts')->insert($chunk);
            $firstIdInChunk = (int) DB::getPdo()->lastInsertId();
            for ($j = 0; $j < count($chunk); $j++) {
                $shiftIds[] = $firstIdInChunk + $j;
            }
        }

        // 8. Create shifts_qualifications and shift_workers
        $this->command->info('Inserting shift qualifications and workers...');

        foreach ($shiftRows as $i => $row) {
            $shiftId = $shiftIds[$i];
            $qualId = $row['_meta_qual_id'];
            $requiredWorkers = $row['_meta_required_workers'];
            $craftId = $row['_meta_craft_id'];

            $qualificationRows[] = [
                'shift_id' => $shiftId,
                'shift_qualification_id' => $qualId,
                'value' => $requiredWorkers,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Assign ~60% of required workers
            $assignCount = max(1, (int) round($requiredWorkers * 0.6));
            $availableUsers = $craftUsers[$craftId] ?? $userIds;
            if (empty($availableUsers)) {
                $availableUsers = $userIds;
            }

            $shuffled = $availableUsers;
            shuffle($shuffled);
            $assignedUsers = array_slice($shuffled, 0, min($assignCount, count($shuffled)));

            foreach ($assignedUsers as $userId) {
                $workerRows[] = [
                    'shift_id' => $shiftId,
                    'employable_type' => 'Artwork\\Modules\\User\\Models\\User',
                    'employable_id' => $userId,
                    'shift_qualification_id' => $qualId,
                    'shift_count' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($qualificationRows, 500) as $chunk) {
            DB::table('shifts_qualifications')->insert($chunk);
        }

        foreach (array_chunk($workerRows, 500) as $chunk) {
            DB::table('shift_workers')->insert($chunk);
        }

        // 9. Summary
        $this->command->info('');
        $this->command->info('=== Mass Data Seeder Complete ===');
        $this->command->info("Users created:              150");
        $this->command->info("Craft assignments:          " . count($craftableRows));
        $this->command->info("Qualification assignments:  " . count($qualifiableRows));
        $this->command->info("Projects created:           " . count($projectIds));
        $this->command->info("Events created:             {$eventCounter}");
        $this->command->info("Shifts created:             {$shiftCounter}");
        $this->command->info("Shift qualifications:       " . count($qualificationRows));
        $this->command->info("Shift workers assigned:     " . count($workerRows));
        $this->command->info("Date range:                 2026-05-19 to 2026-11-18");
        $this->command->info('=============================================');
    }

    private function buildWeightedEventTypes(): void
    {
        $this->weightedEventTypes = [];
        foreach (self::EVENT_TYPE_WEIGHTS as $typeId => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $this->weightedEventTypes[] = $typeId;
            }
        }
    }

    private function randomEventType(): int
    {
        return $this->weightedEventTypes[array_rand($this->weightedEventTypes)];
    }

    private function eventNameForType(int $typeId): string
    {
        return match ($typeId) {
            2 => 'Teammeeting',
            3 => 'Workshop',
            4 => 'Aufführung',
            5 => 'Probe',
            7 => 'Reinigung',
            8 => 'Aufbau/Abbau',
            default => 'Veranstaltung',
        };
    }
}
