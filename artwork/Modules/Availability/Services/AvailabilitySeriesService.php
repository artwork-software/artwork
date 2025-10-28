<?php

namespace Artwork\Modules\Availability\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\Availability\Repositories\AvailabilitySeriesRepository;

class AvailabilitySeriesService
{
    public function __construct(private readonly AvailabilitySeriesRepository $availabilitySeriesRepository,)
    {
    }


    public function create($frequency, $until): AvailabilitySeries|Model
    {
        $availabilitySeries = new AvailabilitySeries();
        $availabilitySeries->frequency = $frequency;
        $availabilitySeries->end_date = $until;
        return $this->availabilitySeriesRepository->save($availabilitySeries);
    }

    public function deleteSeries(AvailabilitySeries $availabilitySeries): void
    {
        $availabilitySeries->availabilities()->each(function ($availability): void {
            // Delete conflicts of each availability, then the availability itself
            $availability->conflicts()->each(function ($conflict): void {
                $conflict->delete();
            });
            $availability->delete();
        });
        // Finally, delete the series record itself
        $availabilitySeries->delete();
    }
}
