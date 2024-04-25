<?php

namespace Database\Factories\Artwork\Modules\Invitation\Models;

use Artwork\Modules\Invitation\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'token' => Str::random(20),
            'permissions' => ['invitation permission factory']
        ];
    }

    public function withToken(string $token): self
    {
        return $this->state([
            'token' => Hash::make($token),
        ]);
    }
}
