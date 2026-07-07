<?php

namespace Database\Seeders;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Random\RandomException;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws RandomException
     */
    public function run(): void
    {

        $dateRange = ['2026-01-01', '2026-01-30'];

        $projects = Project::all()->pluck('id')->toArray();

        $rooms = Room::all()->pluck('id')->toArray();

        foreach ($rooms as $roomId) {
            $numberOfShifts = random_int(5, 20);
            for ($i = 0; $i < $numberOfShifts; $i++) {
                $startDate = date('Y-m-d', random_int(strtotime($dateRange[0]), strtotime($dateRange[1])));
                $startTime = date('H:i:s', random_int(strtotime('08:00:00'), strtotime('18:00:00')));
                $endTime = date(
                    'H:i:s',
                    strtotime($startTime) + random_int(1, 4) * 3600
                );
                $projectId = $projects[array_rand($projects)];

                Shift::create([
                    'start_date' => $startDate,
                    'end_date' => $startDate,
                    'start' => $startTime,
                    'end' => $endTime,
                    'break_minutes' => random_int(0, 30),
                    'description' => 'Shift in room ' . $roomId,
                    // randomly set is_committed to true or false
                    'is_committed' => (random_int(0, 1) === 1),
                    'event_start_day' => $startDate,
                    'event_end_day' => $startDate,
                    'room_id' => $roomId,
                    'project_id' => $projectId,
                    'craft_id' => 1,
                ]);

                /**
                 * protected $fillable = [
                 * 'name',
                 * 'eventName',
                 * 'description',
                 * 'start_time',
                 * 'end_time',
                 * 'occupancy_option',
                 * 'audience',
                 * 'is_loud',
                 * 'event_type_id',
                 * 'room_id',
                 * 'user_id',
                 * 'project_id',
                 * 'series_id',
                 * 'is_series',
                 * 'accepted',
                 * 'option_string',
                 * 'declined_room_id',
                 * 'allDay',
                 * 'latest_end_datetime',
                 * 'earliest_start_datetime',
                 * 'event_status_id',
                 * 'is_planning'
                 * ];
                 */

                Event::create([
                    'name' => 'Event for shift in room ' . $roomId,
                    'description' => 'Event description',
                    'start_time' => $startDate . ' ' . $startTime,
                    'end_time' => $startDate . ' ' . $endTime,
                    'audience' => random_int(10, 100),
                    'is_loud' => (random_int(0, 1) === 1),
                    'event_type_id' => 1,
                    'room_id' => $roomId,
                    'user_id' => 1,
                    'project_id' => $projectId,
                    'is_series' => false,
                    'accepted' => true,
                    'allDay' => false,
                    'event_status_id' => 1,
                    'is_planning' => false
                ]);
            }
        }
    }
}
