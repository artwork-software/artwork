<?php

namespace Database\Factories\Artwork\Modules\MoneySource\Models;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoneySource>
 */
class MoneySourceFactory extends Factory
{
    protected $model = MoneySource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'name' => 'MoneySource ' . $this->faker->unique()->word(),
            'amount' => $this->faker->randomFloat(2, 100, 100000),
            'source_name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'is_group' => false,
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
        ];
    }
}
