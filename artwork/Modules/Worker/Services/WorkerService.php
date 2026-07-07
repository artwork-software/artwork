<?php

namespace Artwork\Modules\Worker\Services;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\Worker\Config\WorkerEagerLoadConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class WorkerService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly FreelancerService $freelancerService,
        private readonly ServiceProviderService $serviceProviderService,
    ) {
    }

    public function searchWorkers(string $search): Collection
    {
        $users = $this->userService->searchUsers($search);
        $freelancers = $this->freelancerService->searchFreelancers($search);
        $serviceProviders = $this->serviceProviderService->searchServiceProviders($search);

        return $users->merge($freelancers)->merge($serviceProviders);
    }

    public function getWorkersForShiftPlan(string $workerType, Carbon|null $startDate = null, Carbon|null $endDate = null): Collection
    {
        $eagerLoads = WorkerEagerLoadConfig::getShiftPlanEagerLoads($startDate, $endDate);
        // Polymorphe Query basierend auf Worker-Typ
        $query = match ($workerType) {
            User::class => User::query()->canWorkShifts(),
            Freelancer::class => Freelancer::query()->canWorkShifts(),
            ServiceProvider::class => ServiceProvider::query()->canWorkShifts(),
            default => throw new \InvalidArgumentException("Unbekannter Worker-Typ: {$workerType}"),
        };

        $workers = $query->with($eagerLoads)->get();

        $this->loadShiftQualificationPivots($workers);

        return $workers;
    }

    public function getWorkersForShiftPlanByIds(
        string $workerType,
        array $ids,
        Carbon|null $startDate = null,
        Carbon|null $endDate = null
    ): Collection {
        if ($ids === []) {
            return new Collection();
        }

        $eagerLoads = WorkerEagerLoadConfig::getShiftPlanEagerLoads($startDate, $endDate);
        $query = match ($workerType) {
            User::class => User::query()->canWorkShifts()->whereIn('id', $ids),
            Freelancer::class => Freelancer::query()->canWorkShifts()->whereIn('id', $ids),
            ServiceProvider::class => ServiceProvider::query()->canWorkShifts()->whereIn('id', $ids),
            default => throw new \InvalidArgumentException("Unbekannter Worker-Typ: {$workerType}"),
        };

        $workers = $query->with($eagerLoads)->get();

        $this->loadShiftQualificationPivots($workers);

        return $workers;
    }

    private function loadShiftQualificationPivots(Collection $workers): void
    {
        $qualificationIds = $workers->flatMap(function ($worker) {
            return $worker->shifts->map(function ($shift) {
                return $shift->pivot?->shift_qualification_id;
            })->filter();
        })->unique();

        if ($qualificationIds->isNotEmpty()) {
            $qualifications = ShiftQualification::whereIn('id', $qualificationIds)->get()->keyBy('id');

            $workers->each(function ($worker) use ($qualifications) {
                $worker->shifts->each(function ($shift) use ($qualifications) {
                    if ($shift->pivot && $shift->pivot->shift_qualification_id) {
                        $shift->pivot->setRelation('shiftQualification', $qualifications->get($shift->pivot->shift_qualification_id));
                    }
                });
            });
        }
    }

    public function buildQualificationsCache(Collection $workers): array
    {
        $qualificationsCache = [];

        foreach ($workers as $worker) {
            foreach ($worker->shifts as $shift) {
                if ($shift->pivot?->shift_qualification_id) {
                    $qualId = $shift->pivot->shift_qualification_id;
                    if (!isset($qualificationsCache[$qualId])) {
                        if ($shift->pivot->relationLoaded('shiftQualification')) {
                            $qualificationsCache[$qualId] = $shift->pivot->getRelation('shiftQualification');
                        }
                    }
                }
            }
        }

        return $qualificationsCache;
    }

    public function mapDayServices(?Collection $dayServices): SupportCollection
    {
        if (!$dayServices) {
            return collect();
        }

        return $dayServices->map(function ($dayService) {
            return [
                'id' => $dayService->id,
                'name' => $dayService->name,
                'icon' => $dayService->icon,
                'hex_color' => $dayService->hex_color,
                'pivot' => [
                    'date' => $dayService->pivot->date ?? null,
                ],
            ];
        });
    }

    public function mapIndividualTimes(?Collection $individualTimes): SupportCollection
    {
        if (!$individualTimes) {
            return collect();
        }

        return $individualTimes->map(function ($individualTime) {
            return [
                'id' => $individualTime->id,
                'title' => $individualTime->title,
                'start_time' => $individualTime->start_time,
                'end_time' => $individualTime->end_time,
                'start_date' => $individualTime->start_date,
                'end_date' => $individualTime->end_date,
                'full_day' => $individualTime->full_day,
                'working_time_minutes' => $individualTime->working_time_minutes,
                'break_minutes' => $individualTime->break_minutes,
                'days_of_individual_time' => $individualTime->days_of_individual_time ?? [],
            ];
        });
    }
}
