<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserCalendarSettings;
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
