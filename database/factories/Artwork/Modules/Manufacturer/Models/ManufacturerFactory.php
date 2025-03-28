<?php

namespace Database\Factories\Artwork\Modules\Manufacturer\Models;

use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\Manufacturer\Models\Manufacturer>
 */
class ManufacturerFactory extends Factory
{

    protected $model = Manufacturer::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /**
         * 'name',
         * 'address',
         * 'website',
         * 'customer_number',
         * 'contact_person',
         * 'phone',
         * 'email',
         */
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'website' => $this->faker->url,
            'customer_number' => $this->faker->randomNumber(5),
            'contact_person' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
        ];
    }
}
