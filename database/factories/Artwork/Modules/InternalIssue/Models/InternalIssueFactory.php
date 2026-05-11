<?php

namespace Database\Factories\Artwork\Modules\InternalIssue\Models;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalIssue>
 */
class InternalIssueFactory extends Factory
{
    protected $model = InternalIssue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'start_date' => $this->faker->date(),
            'start_time' => '08:00',
            'end_date' => $this->faker->date(),
            'end_time' => '16:00',
            'notes' => $this->faker->paragraph(),
            'special_items_done' => false,
        ];
    }
}
