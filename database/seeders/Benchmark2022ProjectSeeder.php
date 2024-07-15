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

class Benchmark2022ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::create([
            'name' => 'Benchmark 2022',
            'description' => 'Per Day 10 Events, 5 Rooms, 2 Events per Room per Day',
            'shift_description' => 'Events of Doom',
            'key_visual_path' => 'M8AUVkujRBdqQu9rbS2Gart.JPG',
            'state' => 4,
        ]);

        $startOfYear = Carbon::today()->year(2022)->startOfYear();
        $endOfYear = clone $startOfYear;
        $endOfYear->endOfYear();
        $dateRange = $startOfYear->range($endOfYear);
        $eventTypes = EventType::all()->pluck('name', 'id');
        //first 5 rooms
        $roomIds = Room::all()->pluck('id')->shift(5);
        $faker = Factory::create('de_DE');

        //generate two events per room per day
        $dateRange->forEach(function (Carbon $startDate) use ($faker, $roomIds, $project, $eventTypes): void {
            $this->command->info('Create Events for Date: ' . $startDate->format('d.m.Y'));
            foreach ($roomIds as $roomId) {
                $startDate->setHours($faker->numberBetween(8, 20));
                $endDate = clone $startDate;
                $endDate->addHours($faker->numberBetween(1, 10));
                $eventName = $eventTypes->random();
                Event::factory()
                    ->set('start_time', $startDate)
                    ->set('end_time', $endDate)
                    ->set('project_id', $project->id)
                    ->set('room_id', $roomId)
                    ->set('eventName', $eventName)
                    ->set('event_type_id', $eventTypes->search($eventName))
                    ->set('allDay', $faker->boolean())
                    ->set('user_id', User::find(1)->id)
                    ->set('audience', $faker->boolean())
                    ->count(2)
                    ->create();
            }
        });
    }
}
