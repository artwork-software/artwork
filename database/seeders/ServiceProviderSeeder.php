<?php

namespace Database\Seeders;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ShiftQualification\Models\ServiceProviderShiftQualification;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\ShiftQualification\Repositories\ServiceProviderShiftQualificationRepository;
use Faker\Factory;
use Illuminate\Database\Seeder;

class ServiceProviderSeeder extends Seeder
{
    public function __construct(
        private readonly ServiceProviderShiftQualificationRepository $serviceProviderShiftQualificationRepository
    ) {
    }

    public function run(): void
    {
        $faker = Factory::create('de_DE');

        for ($i = 0; $i < 10; $i++) {
            $companyName = $faker->company;
            $serviceProvider = ServiceProvider::create([
                'profile_image' => 'https://ui-avatars.com/api/?name=' .
                    $companyName[0] .
                    '&color=7F9CF5&background=EBF4FF',
                'provider_name' => $companyName,
                'email' => $faker->companyEmail,
                'phone_number' => $faker->phoneNumber,
                'street' => $faker->streetName . ' ' . $faker->buildingNumber,
                'zip_code' => $faker->postcode,
                'location' => $faker->city,
                'note' => $faker->realText(250),
                'can_work_shifts' => $faker->boolean()
            ]);

            if ($serviceProvider->can_work_shifts) {
                $serviceProvider->assignedCrafts()->sync(Craft::all()->pluck('id'));
            }

            if ($serviceProvider->can_work_shifts && $faker->boolean) {
                $this->serviceProviderShiftQualificationRepository->save(
                    new ServiceProviderShiftQualification([
                        'service_provider_id' => $serviceProvider->id,
                        'shift_qualification_id' => ShiftQualification::query()->masterQualification()->first()->id
                    ])
                );
            }

            $serviceProvider->contacts()->create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->companyEmail,
                'phone_number' => $faker->phoneNumber,
            ]);
            $serviceProvider->contacts()->create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->companyEmail,
                'phone_number' => $faker->phoneNumber,
            ]);
            $serviceProvider->contacts()->create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->companyEmail,
                'phone_number' => $faker->phoneNumber,
            ]);
            $serviceProvider->contacts()->create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->companyEmail,
                'phone_number' => $faker->phoneNumber,
            ]);
        }
    }
}
