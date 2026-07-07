<?php

namespace Database\Factories\Artwork\Modules\ArtistResidency\Models;

use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtistResidency>
 */
class ArtistResidencyFactory extends Factory
{
    protected $model = ArtistResidency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'arrival_date' => $this->faker->date(),
            'departure_date' => $this->faker->date(),
            'do_not_save_artist' => false,
        ];
    }
}
