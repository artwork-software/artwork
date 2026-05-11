<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColumnCell>
 */
class ColumnCellFactory extends Factory
{
    protected $model = ColumnCell::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'column_id' => Column::factory(),
            'sub_position_row_id' => SubPositionRow::factory(),
            'value' => '0,00',
            'commented' => false,
            'verified_value' => '',
        ];
    }
}
