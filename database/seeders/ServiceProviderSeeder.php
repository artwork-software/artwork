<?php

namespace Database\Seeders;

use App\Models\Freelancer;
use App\Models\ServiceProvider;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fakeFreelancer = Factory::create('de_DE');

        for ($i = 0; $i < 10; $i++){
            $companyName = $fakeFreelancer->company;
            $provider = ServiceProvider::create([
                'profile_image' => 'https://ui-avatars.com/api/?name='. $companyName[0] .'&color=7F9CF5&background=EBF4FF',
                'provider_name' => $companyName,
                'email' => $fakeFreelancer->companyEmail,
                'phone_number' => $fakeFreelancer->phoneNumber,
                'street' => $fakeFreelancer->streetName . ' ' . $fakeFreelancer->buildingNumber,
                'zip_code' => $fakeFreelancer->postcode,
                'location' => $fakeFreelancer->city,
                'note' => $fakeFreelancer->realText(250),
            ]);

            $provider->contacts()->create([
                'first_name' => $fakeFreelancer->firstName,
                'last_name' => $fakeFreelancer->lastName,
                'email' => $fakeFreelancer->companyEmail,
                'phone_number' => $fakeFreelancer->phoneNumber,
            ]);
            $provider->contacts()->create([
                'first_name' => $fakeFreelancer->firstName,
                'last_name' => $fakeFreelancer->lastName,
                'email' => $fakeFreelancer->companyEmail,
                'phone_number' => $fakeFreelancer->phoneNumber,
            ]);
            $provider->contacts()->create([
                'first_name' => $fakeFreelancer->firstName,
                'last_name' => $fakeFreelancer->lastName,
                'email' => $fakeFreelancer->companyEmail,
                'phone_number' => $fakeFreelancer->phoneNumber,
            ]);
            $provider->contacts()->create([
                'first_name' => $fakeFreelancer->firstName,
                'last_name' => $fakeFreelancer->lastName,
                'email' => $fakeFreelancer->companyEmail,
                'phone_number' => $fakeFreelancer->phoneNumber,
            ]);
        }

        $this->command->info("End generate Service Providers");
    }
}
