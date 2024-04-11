<?php

namespace Database\Factories\Artwork\Modules\Vacation\Models;

use Artwork\Modules\Vacation\Models\VacationSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacationSeriesFactory extends Factory
{
    protected $model = VacationSeries::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'frequency' => $this->faker->randomDigitNotNull,
            'end_date' => today()->addDay(),
        ];
    }
}
