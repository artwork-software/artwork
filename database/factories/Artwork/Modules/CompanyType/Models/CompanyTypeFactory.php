<?php

namespace Database\Factories\Artwork\Modules\CompanyType\Models;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyType>
 */
class CompanyTypeFactory extends Factory
{
    protected $model = CompanyType::class;

    public function definition(): array
    {
        return [
            'name' => 'Company Type ' . $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
        ];
    }
}
