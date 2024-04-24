<?php

namespace Database\Factories;

use Artwork\Modules\ContractModule\Models\ContractModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractModule>
 */
class ContractModuleFactory extends Factory
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
            'basename' => 'contract_modules/' . $this->faker->name
        ];
    }
}
