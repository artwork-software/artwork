<?php

namespace Database\Factories\Artwork\Modules\Availability\Models;

use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilitySeriesFactory extends Factory
{
    protected $model = AvailabilitySeries::class;

    /**
     * @return string []
     */
    public function definition(): array
    {
        return [
            'frequency' => 'daily',
            'end_date' => today()->addDay(),
        ];
    }
}
