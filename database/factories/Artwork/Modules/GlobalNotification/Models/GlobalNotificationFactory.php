<?php

namespace Database\Factories\Artwork\Modules\GlobalNotification\Models;

use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GlobalNotification>
 */
class GlobalNotificationFactory extends Factory
{
    protected $model = GlobalNotification::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'image_name' => null,
            'expiration_date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'created_by' => User::factory(),
        ];
    }
}
