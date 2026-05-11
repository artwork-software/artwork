<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\TableColumnOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TableColumnOrder>
 */
class TableColumnOrderFactory extends Factory
{
    protected $model = TableColumnOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'display_text' => $this->faker->words(2, true),
            'position' => $this->faker->numberBetween(0, 100),
        ];
    }
}
