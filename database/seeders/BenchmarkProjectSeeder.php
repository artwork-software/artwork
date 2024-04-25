<?php

namespace Database\Seeders;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BenchmarkProjectSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('de_DE');
        $project = Project::create([
            'name' => 'Benchmark',
            'description' => $faker->text,
            'shift_description' => '',
            'key_visual_path' => 'M8AUVkujRBdqQu9rbS2Gart.JPG',
            'state' => 4
        ]);

        /*
        Room::factory()
            //create 42 rooms additional to currently existing 8 = 50
            ->count(42)
            ->create();
        */

        $startOfYear = Carbon::today()->startOfYear();
        $endOfYear = clone $startOfYear;
        $endOfYear->endOfYear();
        $dateRange = $startOfYear->range($endOfYear);
        $eventTypes = EventType::all()->pluck('name', 'id');
        $roomIds = Room::all()->pluck('id');

        //generate two events per room per day
        $dateRange->forEach(function (Carbon $startDate) use ($faker, $roomIds, $project, $eventTypes): void {
            $this->command->info('Create Events for Date: ' . $startDate->format('d.m.Y'));
            for ($x = 0; $x <= 1; $x++) {
                $startDate->setHours($faker->numberBetween(8, 20));
                $endDate = clone $startDate;
                $endDate->addHours($faker->numberBetween(1, 10));
                $eventName = $eventTypes->random();

                $eventsWithShift = Event::factory()
                    ->set('start_time', $startDate)
                    ->set('end_time', $endDate)
                    ->set('project_id', $project->id)
                    ->set('room_id', $roomIds->random())
                    ->set('eventName', $eventName)
                    ->set('event_type_id', $eventTypes->search($eventName))
                    ->set('allDay', $faker->boolean())
                    ->set('user_id', User::find(1)->id)
                    ->set('audience', $faker->boolean())
                    ->count(1)
                    ->create();

                foreach ($eventsWithShift as $eventWithShift) {
                    $eventWithShift->shifts()->create([
                        'start' => '10:00:00',
                        'end' => '13:00:00',
                        'break_minutes' => '11',
                        'craft_id' => 1,
                        'number_employees' => 3,
                        'shift_uuid' => Str::uuid(),
                        'event_start_day' => $eventWithShift->start_time->format('Y-m-d'),
                        'event_end_day' => $eventWithShift->end_time->format('Y-m-d'),
                    ]);
                }
            }

//            foreach ($roomIds as $roomId) {
//                $startDate->setHours($faker->numberBetween(8, 20));
//                $endDate = clone $startDate;
//                $endDate->addHours($faker->numberBetween(1, 10));
//                $eventName = $eventTypes->random();
//                Event::factory()
//                    ->set('start_time', $startDate)
//                    ->set('end_time', $endDate)
//                    ->set('project_id', $project->id)
//                    ->set('room_id', $roomId)
//                    ->set('eventName', $eventName)
//                    ->set('event_type_id', $eventTypes->search($eventName))
//                    ->set('allDay', $faker->boolean())
//                    ->set('user_id', User::find(1)->id)
//                    ->set('audience', $faker->boolean())
//                    ->count(2)
//                    ->create();
//            }
        });
    }
}
