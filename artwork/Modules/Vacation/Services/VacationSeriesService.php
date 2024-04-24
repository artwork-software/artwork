<?php

namespace Artwork\Modules\Vacation\Services;

use Artwork\Modules\Vacation\Models\VacationSeries;
use Artwork\Modules\Vacation\Repository\VacationSeriesRepository;

readonly class VacationSeriesService
{
    public function __construct(private VacationSeriesRepository $vacationSeriesRepository)
    {
    }

    public function create(string $frequency, string $until): VacationSeries
    {
        $vacationSeries = new VacationSeries([
            'frequency' => $frequency,
            'end_date' => $until
        ]);

        $this->vacationSeriesRepository->save($vacationSeries);

        return $vacationSeries;
    }

    public function deleteSeries(VacationSeries $vacationSeries): void
    {
        $vacationSeries->vacations()->each(function ($vacation): void {
            $vacation->each(function ($vacationConflict): void {
                $vacationConflict->delete();
            });
            $vacation->delete();
        });
        $vacationSeries->delete();
    }
}
