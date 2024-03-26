<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'basename' => '',
            'contract_partner' => 'Agentur XYZ',
            'amount' => 20000,
            'project_id' => 1,
            'description' => $this->faker->text(255),
            'ksk_liable' => false,
            'resident_abroad' => false,
        ];
    }
}
