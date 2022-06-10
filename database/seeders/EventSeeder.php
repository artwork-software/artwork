<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('events')->insert([
            'name' => 'TestEvent',
            'description' => null,
            'start_time' => '2022-05-29T14:00',
            'end_time' => '2022-05-30T16:00',
            'occupancy_option' => null,
            'audience' => null,
            'is_loud' => null,
            'event_type_id' => 1,
            'room_id' => 1,
            'project_id' => 1,
            'user_id' => 1
        ]);
    }
}
