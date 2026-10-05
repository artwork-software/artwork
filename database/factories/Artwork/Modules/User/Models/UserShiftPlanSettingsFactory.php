<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftPlanSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftPlanSettings>
 */
class UserShiftPlanSettingsFactory extends Factory
{
    protected $model = UserShiftPlanSettings::class;

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
