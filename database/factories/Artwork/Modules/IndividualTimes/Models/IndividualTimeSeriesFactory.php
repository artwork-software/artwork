<?php

namespace Database\Factories\Artwork\Modules\IndividualTimes\Models;

use Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndividualTimeSeries>
 */
class IndividualTimeSeriesFactory extends Factory
{
    protected $model = IndividualTimeSeries::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'weekdays' => [],
        ];
    }
}
