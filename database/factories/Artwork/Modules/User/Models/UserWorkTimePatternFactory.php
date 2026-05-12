<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\UserWorkTimePattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserWorkTimePattern>
 */
class UserWorkTimePatternFactory extends Factory
{
    protected $model = UserWorkTimePattern::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'monday' => '08:00:00',
            'tuesday' => '08:00:00',
            'wednesday' => '08:00:00',
            'thursday' => '08:00:00',
            'friday' => '08:00:00',
            'saturday' => '00:00:00',
            'sunday' => '00:00:00',
        ];
    }
}
