<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserInventoryArticlePlanFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserInventoryArticlePlanFilter>
 */
class UserInventoryArticlePlanFilterFactory extends Factory
{
    protected $model = UserInventoryArticlePlanFilter::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
