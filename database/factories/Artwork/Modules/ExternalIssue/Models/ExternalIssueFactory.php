<?php

namespace Database\Factories\Artwork\Modules\ExternalIssue\Models;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalIssue>
 */
class ExternalIssueFactory extends Factory
{
    protected $model = ExternalIssue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'external_name' => $this->faker->name(),
            'external_address' => $this->faker->address(),
            'external_email' => $this->faker->safeEmail(),
            'external_phone' => $this->faker->phoneNumber(),
            'issue_date' => $this->faker->date(),
            'return_date' => $this->faker->date(),
            'material_value' => $this->faker->randomFloat(2, 0, 1000),
            'special_items_done' => false,
        ];
    }
}
