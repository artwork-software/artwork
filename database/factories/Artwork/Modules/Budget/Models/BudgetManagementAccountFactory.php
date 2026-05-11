<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetManagementAccount>
 */
class BudgetManagementAccountFactory extends Factory
{
    protected $model = BudgetManagementAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_number' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'title' => $this->faker->words(2, true),
            'is_account_for_revenue' => false,
        ];
    }
}
