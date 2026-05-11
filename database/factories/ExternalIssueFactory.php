<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\ExternalIssue\Models\ExternalIssue>
 */
class ExternalIssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
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
            'return_date' => null,
            'material_value' => $this->faker->randomFloat(2, 0, 1000),
        ];
    }
}
