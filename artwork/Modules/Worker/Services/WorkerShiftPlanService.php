<?php

namespace Artwork\Modules\Worker\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Abstracts\WorkerShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

readonly class WorkerShiftPlanService
{
    public function __construct(
        private WorkerService $workerService,
    ) {
    }

    public function loadWorkerRelations(Collection $workers, Carbon $startDate, Carbon $endDate): Collection
    {
        $eagerLoads = [
            'individualTimes' => function ($query) use ($startDate, $endDate): void {
                $query->select(['id', 'title', 'start_time', 'end_time', 'start_date', 'end_date', 'full_day', 'working_time_minutes', 'break_minutes', 'series_uuid', 'timeable_type', 'timeable_id'])
                    ->with(['series:uuid,title'])
                    ->individualByDateRange($startDate->toDateString(), $endDate->toDateString());
            },
            'shiftPlanComments' => function ($query) use ($startDate, $endDate): void {
                $query->select(['id', 'comment', 'date', 'created_by', 'commentable_type', 'commentable_id'])
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
            },
        ];

        // User-spezifische Relations
        $firstWorker = $workers->first();
        if ($firstWorker instanceof User) {
            $eagerLoads['workTimeBookings'] = function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('booking_day', [$startDate->toDateString(), $endDate->toDateString()]);
            };
            $eagerLoads['workTimes'] = function ($query) use ($startDate, $endDate): void {
                $query->where(function ($q) use ($endDate): void {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $endDate);
                })->where(function ($q) use ($startDate): void {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $startDate);
                });
            };
        }

        return $workers->load($eagerLoads);
    }

    /**
     * Filtert Workers nach Qualifications, falls der aktuelle User Filter gesetzt hat
     */
    public function filterByQualifications(Collection $workers, ?User $currentUser): Collection
    {
        if (!$currentUser || empty($currentUser->getAttribute('show_qualifications'))) {
            return $workers;
        }

        $selectedQualifications = $currentUser->getAttribute('show_qualifications');

        return $workers->filter(function ($worker) use ($selectedQualifications) {
            $workerQualificationIds = $worker->shiftQualifications->pluck('id')->toArray();
            return !empty(array_intersect($selectedQualifications, $workerQualificationIds));
        });
    }

    public function buildWorkerData(
        User|Freelancer|ServiceProvider $worker,
        JsonResource $resource,
        array $qualificationsCache,
        Carbon $startDate,
        Carbon $endDate,
        bool $addVacationsAndAvailabilities = false,
        array $additionalData = []
    ): array {
        if ($resource instanceof WorkerShiftPlanResource) {
            $resource->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setQualificationsCache($qualificationsCache);
        }

        $workerData = [
            $this->getWorkerKey($worker) => $resource->resolve(),
            'dayServices' => $this->workerService->mapDayServices($worker->dayServices)->groupBy('pivot.date'),
            'individual_times' => $this->workerService->mapIndividualTimes($worker->individualTimes),
            'shift_comments' => $worker->getShiftPlanCommentsForPeriod($startDate->toDateString(), $endDate->toDateString()),
        ];

        if ($worker instanceof User) {
            $workerData['is_freelancer'] = $worker->getAttribute('is_freelancer');
        }

        $workerData = array_merge($workerData, $additionalData);

        if ($addVacationsAndAvailabilities) {
            $workerData['vacations'] = $worker->getVacationDays();
        }

        return $workerData;
    }

    private function getWorkerKey(User|Freelancer|ServiceProvider $worker): string
    {
        return match (true) {
            $worker instanceof User => 'user',
            $worker instanceof Freelancer => 'freelancer',
            $worker instanceof ServiceProvider => 'service_provider',
            default => 'worker',
        };
    }
}
