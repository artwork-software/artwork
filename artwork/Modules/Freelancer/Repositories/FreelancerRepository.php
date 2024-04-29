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

    public function findFreelancerOrFail($freelancerId): Freelancer
    {
        return Freelancer::findOrFail($freelancerId);
    }

    public function getAvailabilitiesBetweenDatesGroupedByFormattedDate(
        int|Freelancer $freelancer,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        if (!$freelancer instanceof Freelancer) {
            $freelancer = $this->findFreelancerOrFail($freelancer);
        }

        return $freelancer->availabilities()->betweenDates($startDate, $endDate)->get()->groupBy('formatted_date');
    }
}
