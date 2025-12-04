<?php

namespace Database\Seeders;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $craftIds = Craft::pluck('id')->toArray();

        if (empty($craftIds)) {
            $this->command->info('Keine Gewerke gefunden. Seeder wird abgebrochen.');
            return;
        }

        $total = 300;

        for ($i = 0; $i < $total; $i++) {
            $first = $faker->firstName();
            $last = $faker->lastName();

            $email = strtolower(sprintf('%s.%s.%d@example.test', $first, $last, $i));

            $user = User::create([
                'first_name'       => $first,
                'last_name'        => $last,
                'email'            => $email,
                'phone_number'     => $faker->phoneNumber(),
                'password'         => Hash::make('password'), // standard pass für Tests
                'position'         => $faker->jobTitle(),
                'language'         => $faker->randomElement(['de', 'en']),
                'opened_checklists' => [],
                'opened_areas'     => [],
                'can_work_shifts'  => $faker->boolean(80),
                'work_time_balance' => $faker->numberBetween(-4800, 4800), // Minuten
            ]);

            // zufällige 1-5 Gewerke anhängen (falls vorhanden)
            $attachCount = $faker->numberBetween(1, min(5, count($craftIds)));
            $attachIds = $faker->randomElements($craftIds, $attachCount);

            // morphToMany attach() akzeptiert IDs-Array
            $user->assignedCrafts()->attach($attachIds);
        }

        $this->command->info("{$total} Test-User erstellt und zufällig Gewerke zugewiesen.");
    }
}
