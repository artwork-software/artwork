<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\Column;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetSumDetails>
 */
class BudgetSumDetailsFactory extends Factory
{
    protected $model = BudgetSumDetails::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'column_id' => Column::factory(),
            'type' => 'COST',
        ];
    }
}
