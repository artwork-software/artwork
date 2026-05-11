<?php

namespace Database\Factories\Artwork\Modules\MoneySource\Models;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoneySourceTask>
 */
class MoneySourceTaskFactory extends Factory
{
    protected $model = MoneySourceTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'money_source_id' => MoneySource::factory(),
            'name' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'deadline' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'creator' => User::factory(),
            'done' => false,
        ];
    }
}
