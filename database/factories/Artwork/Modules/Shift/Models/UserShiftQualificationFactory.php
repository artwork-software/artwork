<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\UserShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftQualification>
 */
class UserShiftQualificationFactory extends Factory
{
    protected $model = UserShiftQualification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shift_qualification_id' => ShiftQualification::factory(),
        ];
    }
}
