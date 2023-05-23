<?php

namespace Database\Seeders;

use App\Models\Freelancer;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FreelanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ///profile-photos/jimmy-fermin-bqe0J0b26RQ-unsplash.jpg
        $fakeFreelancer = Factory::create('de_DE');

        for ($i = 0; $i < 10; $i++){
            $firstName = $fakeFreelancer->firstName;
            $lastName = $fakeFreelancer->lastName;
            Freelancer::create([
                'position' => 'Techniker',
                'profile_image' => 'https://ui-avatars.com/api/?name='. $firstName[0] .'+'. $lastName[0] .'&color=7F9CF5&background=EBF4FF',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $fakeFreelancer->email,
                'phone_number' => $fakeFreelancer->phoneNumber,
                'street' => $fakeFreelancer->streetName . ' ' . $fakeFreelancer->buildingNumber,
                'zip_code' => $fakeFreelancer->postcode,
                'location' => $fakeFreelancer->city,
                'note' => $fakeFreelancer->realText(250),
            ]);
        }

        $this->command->info("End generate Freelancers");
    }

}
