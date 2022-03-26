<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'token' => Str::random(20),
            'user_id' => $this->faker->numberBetween([0], [1000]),
            'permissions' => ['invitation permission factory']
        ];
    }
}
