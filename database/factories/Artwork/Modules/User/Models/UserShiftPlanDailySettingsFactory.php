<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftPlanDailySettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftPlanDailySettings>
 */
class UserShiftPlanDailySettingsFactory extends Factory
{
    protected $model = UserShiftPlanDailySettings::class;

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
