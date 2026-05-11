<?php

namespace Database\Factories\Artwork\Modules\Contract\Models;

use Artwork\Modules\Contract\Models\ContractType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractType>
 */
class ContractTypeFactory extends Factory
{
    protected $model = ContractType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'ContractType ' . $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
        ];
    }
}
