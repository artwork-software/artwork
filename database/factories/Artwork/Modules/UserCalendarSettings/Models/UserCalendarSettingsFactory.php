<?php

namespace Database\Factories\Artwork\Modules\UserCalendarSettings\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserCalendarSettingsFactory extends Factory
{
    protected $model = UserCalendarSettings::class;

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
