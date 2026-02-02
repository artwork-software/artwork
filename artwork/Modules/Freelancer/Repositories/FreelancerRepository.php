<?php

namespace Artwork\Modules\Freelancer\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection as SupportCollection;

class FreelancerRepository extends BaseRepository
{
    public function __construct(private readonly Freelancer $freelancer)
    {
    }

    public function getNewModelInstance(): Freelancer
    {
        return $this->freelancer->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->freelancer->newModelQuery();
    }

    public function getWorkers(): Collection
    {
        // Im Konstruktor kann das zu circluar dependency führen, deswegen über den Container
        $workerService = app(\Artwork\Modules\Worker\Services\WorkerService::class);
        return $workerService->getWorkersForShiftPlan(Freelancer::class);
    }

    public function getWorkersByIds(
        array $freelancerIds,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $workerService = app(\Artwork\Modules\Worker\Services\WorkerService::class);
        return $workerService->getWorkersForShiftPlanByIds(
            Freelancer::class,
            $freelancerIds,
            $startDate,
            $endDate
        );
    }

    public function findWorker(int $workerId): Freelancer|null
    {
        return Freelancer::query()->canWorkShifts()->where('id', $workerId)->first();
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

    public function scoutSearch(string $search): SupportCollection
    {
        return $this
            ->getNewModelInstance()
            ->search($search)
            ->get()
            ->map(
                fn(Freelancer $freelancer) => [
                    'id' => $freelancer->getAttribute('id'),
                    'first_name' => $freelancer->getAttribute('first_name'),
                    'last_name' => $freelancer->getAttribute('last_name'),
                    'profile_photo_url' => $freelancer->getAttribute('profile_photo_url'),
                    'manager_type' => $freelancer::class
                ]
            );
    }
}
