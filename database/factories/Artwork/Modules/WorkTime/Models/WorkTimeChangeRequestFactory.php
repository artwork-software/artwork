<?php

namespace Database\Factories\Artwork\Modules\WorkTime\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkTimeChangeRequest>
 */
class WorkTimeChangeRequestFactory extends Factory
{
    protected $model = WorkTimeChangeRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'request_start_time' => '10:00:00',
            'request_end_time' => '10:00:00',
        ];
    }
}
