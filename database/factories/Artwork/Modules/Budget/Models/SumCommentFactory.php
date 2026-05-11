<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SumComment>
 */
class SumCommentFactory extends Factory
{
    protected $model = SumComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commentable_type' => BudgetSumDetails::class,
            'commentable_id' => 1,
            'comment' => $this->faker->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
