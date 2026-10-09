<?php

namespace Database\Factories\Artwork\Modules\IndividualTimes\Models;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndividualTime>
 */
class IndividualTimeFactory extends Factory
{
    protected $model = IndividualTime::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timeable_type' => (new User())->getMorphClass(),
            'timeable_id' => User::factory(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ];
    }
}
