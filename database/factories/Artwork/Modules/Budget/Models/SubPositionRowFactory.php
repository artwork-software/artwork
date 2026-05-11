<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubPositionRow>
 */
class SubPositionRowFactory extends Factory
{
    protected $model = SubPositionRow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sub_position_id' => SubPosition::factory(),
            'position' => 0,
            'order' => 0,
            'commented' => false,
        ];
    }
}
