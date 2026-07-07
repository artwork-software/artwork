<?php

namespace Database\Factories\Artwork\Modules\Accommodation\Models;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Accommodation>
 */
class AccommodationFactory extends Factory
{
    protected $model = Accommodation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->safeEmail(),
            'phone_number' => $this->faker->phoneNumber(),
            'street' => $this->faker->streetAddress(),
            'zip_code' => $this->faker->postcode(),
            'location' => $this->faker->city(),
            'note' => $this->faker->sentence(),
            'profile_image' => null,
        ];
    }
}
