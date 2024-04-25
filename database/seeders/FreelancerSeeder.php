<?php

namespace Database\Seeders;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ShiftQualification\Models\FreelancerShiftQualification;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\ShiftQualification\Repositories\FreelancerShiftQualificationRepository;
use Faker\Factory;
use Illuminate\Database\Seeder;

class FreelancerSeeder extends Seeder
{
    public function __construct(
        private readonly FreelancerShiftQualificationRepository $freelancerShiftQualificationRepository
    ) {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $faker = Factory::create('de_DE');

        for ($i = 0; $i < 10; $i++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $freelancer = Freelancer::create([
                'position' => 'Techniker',
                'profile_image' => 'https://ui-avatars.com/api/?name=' . $firstName[0] . '+' .
                    $lastName[0] . '&color=7F9CF5&background=EBF4FF',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $faker->email,
                'phone_number' => $faker->phoneNumber,
                'street' => $faker->streetName . ' ' . $faker->buildingNumber,
                'zip_code' => $faker->postcode,
                'location' => $faker->city,
                'note' => $faker->realText(250),
                'can_work_shifts' => $faker->boolean()
            ]);

            if ($freelancer->can_work_shifts) {
                $freelancer->assignedCrafts()->sync(Craft::all()->pluck('id'));
            }

            if ($freelancer->can_work_shifts && $faker->boolean) {
                $this->freelancerShiftQualificationRepository->save(
                    new FreelancerShiftQualification([
                        'freelancer_id' => $freelancer->id,
                        'shift_qualification_id' => ShiftQualification::query()->masterQualification()->first()->id
                    ])
                );
            }
        }
    }
}
