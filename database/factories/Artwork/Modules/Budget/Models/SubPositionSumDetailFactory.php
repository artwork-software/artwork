<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubPositionSumDetail>
 */
class SubPositionSumDetailFactory extends Factory
{
    protected $model = SubPositionSumDetail::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sub_position_id' => SubPosition::factory(),
            'column_id' => Column::factory(),
        ];
    }
}
