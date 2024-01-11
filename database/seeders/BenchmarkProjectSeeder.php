<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;

class BenchmarkProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::create([
            'name' => 'Benchmark',
            'description' => 'Make full the Datenbank',
            'shift_description' => 'Events of Doom',
            'key_visual_path' => 'M8AUVkujRBdqQu9rbS2Gart.JPG',
            'state' => 4,
            'num_of_guests' => 23,
            'entry_fee' => 10,
            'registration_required' => true,
            'register_by' => 'email',
            'closed_society' => false,
        ]);

        Room::factory()
            //create 42 rooms additional to currently existing 8 = 50
            ->count(42)
            ->create();

        $startOfYear = Carbon::today()->startOfYear();
        $endOfYear = clone $startOfYear;
        $endOfYear->endOfYear();
        $dateRange = $startOfYear->range($endOfYear);
        $eventTypes = EventType::all()->pluck('name', 'id');
        $roomIds = Room::all()->pluck('id');
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
