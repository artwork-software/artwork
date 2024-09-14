<?php

namespace Database\Seeders;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Illuminate\Database\Seeder;

class WorkerAvailabilitiesAndVacationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (User::all() as $user) {
            Vacation::factory(5000)
                ->set('vacationer_id', $user->id)
                ->set('vacationer_type', User::class)->create();
            Availability::factory(5000)
                ->set('available_id', $user->id)
                ->set('available_type', User::class)->create();
        }

        foreach (Freelancer::all() as $freelancer) {
            Vacation::factory(5000)
                ->set('vacationer_id', $freelancer->id)
                ->set('vacationer_type', Freelancer::class)->create();
            Availability::factory(5000)
                ->set('available_id', $freelancer->id)
                ->set('available_type', Freelancer::class)->create();
        }
    }
}
