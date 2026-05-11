<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionVerified;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubPositionVerified>
 */
class SubPositionVerifiedFactory extends Factory
{
    protected $model = SubPositionVerified::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sub_position_id' => SubPosition::factory(),
            'requested_by' => User::factory(),
            'requested' => 0,
        ];
    }
}
