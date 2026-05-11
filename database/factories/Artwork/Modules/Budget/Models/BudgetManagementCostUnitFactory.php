<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetManagementCostUnit>
 */
class BudgetManagementCostUnitFactory extends Factory
{
    protected $model = BudgetManagementCostUnit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cost_unit_number' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'title' => $this->faker->words(2, true),
        ];
    }
}
