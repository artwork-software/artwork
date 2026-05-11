<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetColumnSetting>
 */
class BudgetColumnSettingFactory extends Factory
{
    protected $model = BudgetColumnSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'column_position' => $this->faker->numberBetween(0, 50),
            'column_name' => $this->faker->word(),
        ];
    }
}
