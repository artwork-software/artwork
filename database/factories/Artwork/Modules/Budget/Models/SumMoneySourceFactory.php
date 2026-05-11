<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SumMoneySource>
 */
class SumMoneySourceFactory extends Factory
{
    protected $model = SumMoneySource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sourceable_type' => BudgetSumDetails::class,
            'sourceable_id' => BudgetSumDetails::factory(),
            'money_source_id' => MoneySource::factory(),
            'linked_type' => 'budget',
        ];
    }
}
