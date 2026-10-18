<?php

namespace Database\Factories\Artwork\Modules\InternalIssue\Models;

use Artwork\Modules\InternalIssue\Models\SpecialItem;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecialItem>
 */
class SpecialItemFactory extends Factory
{
    protected $model = SpecialItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issuable_type' => (new User())->getMorphClass(),
            'issuable_id' => User::factory(),
            'name' => fake()->words(2, true),
            'quantity' => fake()->numberBetween(1, 1000),
        ];
    }
}
