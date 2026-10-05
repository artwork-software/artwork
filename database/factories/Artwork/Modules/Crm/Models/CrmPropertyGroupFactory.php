<?php

namespace Database\Factories\Artwork\Modules\Crm\Models;

use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmPropertyGroup>
 */
class CrmPropertyGroupFactory extends Factory
{
    protected $model = CrmPropertyGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
        ];
    }
}
