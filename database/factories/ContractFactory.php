<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'basename' => '',
            'contract_partner' => 'Agentur XYZ',
            'amount' => 20000,
            'project_id' => 1,
            'description' => $this->faker->paragraph,
            'ksk_liable' => false,
            'resident_abroad' => false,
            'legal_form' => 'GbR',
            'type' => 'Dienstvertrag'
        ];
    }
}
