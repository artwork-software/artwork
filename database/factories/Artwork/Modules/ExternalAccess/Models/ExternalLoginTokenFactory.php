<?php

namespace Database\Factories\Artwork\Modules\ExternalAccess\Models;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExternalLoginToken>
 */
class ExternalLoginTokenFactory extends Factory
{
    protected $model = ExternalLoginToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_access_id' => fn () => ExternalAccess::factory()->active()->create()->id,
            'token_hash' => fn () => hash('sha256', Str::random(64)),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function valid(): self
    {
        return $this->state([
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
        ]);
    }

    public function expired(): self
    {
        return $this->state([
            'expires_at' => now()->subMinute(),
            'used_at' => null,
        ]);
    }

    public function used(): self
    {
        return $this->state([
            'used_at' => now(),
        ]);
    }
}
