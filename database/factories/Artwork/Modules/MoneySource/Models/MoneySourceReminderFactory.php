<?php

namespace Database\Factories\Artwork\Modules\MoneySource\Models;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoneySourceReminder>
 */
class MoneySourceReminderFactory extends Factory
{
    protected $model = MoneySourceReminder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'money_source_id' => MoneySource::factory(),
            'type' => 'expiration',
            'value' => fake()->numberBetween(1, 1000),
        ];
    }
}
