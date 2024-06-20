<?php

namespace Database\Factories\Artwork\Modules\CostCenter\Models;

use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Cost Center ' . $this->faker->name,
        ];
    }
}
