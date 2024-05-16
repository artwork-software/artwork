<?php

namespace Artwork\Modules\Freelancer\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

readonly class FreelancerRepository extends BaseRepository
{
    public function getWorkers(): Collection
    {
        return Freelancer::query()->canWorkShifts()->get();
    }

    public function findOrFail($freelancerId): Freelancer
    {
        return Freelancer::findOrFail($freelancerId);
    }

    public function getAvailabilitiesBetweenDatesGroupedByFormattedDate(
        int|Freelancer $freelancer,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        if (!$freelancer instanceof Freelancer) {
            $freelancer = $this->findOrFail($freelancer);
        }

        return $freelancer->availabilities()->betweenDates($startDate, $endDate)->get()->groupBy('formatted_date');
    }

    public function getVacationsByDateOrderedByDateAscending(
        int|Freelancer $freelancer,
        Carbon $selectedDate
    ): Collection {
        if (!$freelancer instanceof Freelancer) {
            $freelancer = $this->findOrFail($freelancer);
        }

        return $freelancer
            ->vacations()
            ->byDate($selectedDate)
            ->orderedByDate()
            ->get();
    }

    public function getAvailabilitiesByDateOrderedByDateAscending(
        int|Freelancer $freelancer,
        Carbon $selectedDate
    ): Collection {
        if (!$freelancer instanceof Freelancer) {
            $freelancer = $this->findOrFail($freelancer);
        }

        return $freelancer
            ->availabilities()
            ->byDate($selectedDate)
            ->orderedByDate()
            ->get();
    }

    public function getShiftsWithEventsOrderedByStart(int|Freelancer $freelancer): Collection
    {
        if (!$freelancer instanceof Freelancer) {
            $freelancer = $this->findOrFail($freelancer);
        }

        return  $freelancer
            ->shifts()
            ->with(['event', 'event.project', 'event.room'])
            ->orderedByStart()
            ->get();
    }
}
