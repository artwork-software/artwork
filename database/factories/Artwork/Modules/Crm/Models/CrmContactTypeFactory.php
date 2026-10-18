<?php

namespace Database\Factories\Artwork\Modules\Crm\Models;

use Artwork\Modules\Crm\Models\CrmContactType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmContactType>
 */
class CrmContactTypeFactory extends Factory
{
    protected $model = CrmContactType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->bothify('Slug-####-????'),
        ];
    }
}
