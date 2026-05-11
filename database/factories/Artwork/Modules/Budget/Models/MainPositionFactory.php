<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MainPosition>
 */
class MainPositionFactory extends Factory
{
    protected $model = MainPosition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_id' => Table::factory(),
            'type' => 'BUDGET_TYPE_COST',
            'name' => $this->faker->words(2, true),
            'position' => 0,
            'is_verified' => 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED',
            'is_fixed' => false,
        ];
    }

    public function earning(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'BUDGET_TYPE_EARNING',
        ]);
    }
}
