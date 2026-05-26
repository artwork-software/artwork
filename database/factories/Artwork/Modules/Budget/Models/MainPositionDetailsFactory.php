<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MainPositionDetails>
 */
class MainPositionDetailsFactory extends Factory
{
    protected $model = MainPositionDetails::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'main_position_id' => MainPosition::factory(),
            'column_id' => Column::factory(),
        ];
    }
}
