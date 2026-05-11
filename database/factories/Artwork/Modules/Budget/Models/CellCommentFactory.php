<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CellComment>
 */
class CellCommentFactory extends Factory
{
    protected $model = CellComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'column_cell_id' => ColumnCell::factory(),
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
        ];
    }
}
