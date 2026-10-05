<?php

namespace Database\Factories\Artwork\Modules\Contract\Models;

use Artwork\Modules\Contract\Models\ContractModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractModule>
 */
class ContractModuleFactory extends Factory
{
    protected $model = ContractModule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'basename' => fake()->unique()->bothify('Basename-####-????'),
        ];
    }
}
