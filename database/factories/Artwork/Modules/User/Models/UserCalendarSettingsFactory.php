<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Alias-Factory, damit Laravel die Factory nach Standard-Konventionen
 * (basierend auf dem Model-Namespace) auflösen kann.
 */
class UserCalendarSettingsFactory extends Factory
{
    protected $model = UserCalendarSettings::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
