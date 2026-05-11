<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionVerified;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MainPositionVerified>
 */
class MainPositionVerifiedFactory extends Factory
{
    protected $model = MainPositionVerified::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'main_position_id' => MainPosition::factory(),
            'requested_by' => User::factory(),
            'requested' => 0,
        ];
    }
}
