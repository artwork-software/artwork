<?php

namespace Database\Factories\Artwork\Modules\Notification\Models;

use Artwork\Modules\Notification\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationSetting>
 */
class NotificationSettingFactory extends Factory
{
    protected $model = NotificationSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \Artwork\Modules\User\Models\User::factory(),
            'group_type' => \Artwork\Modules\Notification\Enums\NotificationEnum::cases()[0]->groupType(),
            'type' => \Artwork\Modules\Notification\Enums\NotificationEnum::cases()[0]->value,
            'title' => fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }
}
