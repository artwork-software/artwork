<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCommentedBudgetItemsSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserCommentedBudgetItemsSetting>
 */
class UserCommentedBudgetItemsSettingFactory extends Factory
{
    protected $model = UserCommentedBudgetItemsSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exclude' => false,
        ];
    }
}
