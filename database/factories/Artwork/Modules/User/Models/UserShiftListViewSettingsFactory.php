<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftListViewSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftListViewSettings>
 */
class UserShiftListViewSettingsFactory extends Factory
{
    protected $model = UserShiftListViewSettings::class;

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
