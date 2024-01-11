<?php

namespace Artwork\Modules\Vacation\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Vacation\Models\VacationSeries;
use Artwork\Modules\Vacation\Repository\VacationSeriesRepository;

class VacationSeriesService
{
    public function __construct(
        private VacationSeriesRepository $vacationSeriesRepository,
    ) {
    }

    public function create($frequency, $until): VacationSeries|Model
    {
        $vacationSeries = new VacationSeries();
        $vacationSeries->frequency = $frequency;
        $vacationSeries->end_date = $until;
        return $this->vacationSeriesRepository->save($vacationSeries);
    }
}
