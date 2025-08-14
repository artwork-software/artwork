<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftRuleViolationFactory extends Factory
{
    protected $model = ShiftRuleViolation::class;

    public function definition(): array
    {
        return [
            'shift_rule_id' => ShiftRule::factory(),
            'shift_id' => Shift::factory(),
            'user_id' => User::factory(),
            'violation_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'violation_data' => [
                'planned_hours' => $this->faker->randomFloat(1, 8, 12),
                'max_allowed' => 8.0,
                'message' => 'Test violation message'
            ],
            'severity' => $this->faker->randomElement(['warning', 'error']),
            'status' => 'active',
        ];
    }

    public function resolved(int $resolvedBy = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);
    }

    public function ignored(int $resolvedBy = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);
    }
}