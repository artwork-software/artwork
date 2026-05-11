<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RowComment>
 */
class RowCommentFactory extends Factory
{
    protected $model = RowComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sub_position_row_id' => SubPositionRow::factory(),
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
        ];
    }
}
