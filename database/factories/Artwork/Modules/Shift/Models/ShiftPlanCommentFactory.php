<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftPlanComment;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftPlanComment>
 */
class ShiftPlanCommentFactory extends Factory
{
    protected $model = ShiftPlanComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->toDateString(),
            'commentable_type' => (new User())->getMorphClass(),
            'commentable_id' => User::factory(),
        ];
    }
}
