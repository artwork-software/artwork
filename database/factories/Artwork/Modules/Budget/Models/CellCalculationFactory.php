<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\ColumnCell;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CellCalculation>
 */
class CellCalculationFactory extends Factory
{
    protected $model = CellCalculation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cell_id' => ColumnCell::factory(),
            'name' => $this->faker->word(),
            'value' => $this->faker->numberBetween(0, 1000),
            'description' => $this->faker->sentence(3),
            'position' => 0,
        ];
    }
}
