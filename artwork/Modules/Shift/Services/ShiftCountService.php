<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

readonly class ShiftCountService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftUserRepository $shiftUserRepository,
        private ShiftFreelancerRepository $shiftFreelancerRepository,
        private ShiftServiceProviderRepository $shiftServiceProviderRepository
    ) {
    }

    private function getPossiblyCollidingShifts(Shift $shift): Collection
    {
        return $this
            ->shiftRepository
            ->getShiftsBetweenEventStartDayAndEventEndDayStartAndEndTimeOverlapByProjectEventIds(
                Carbon::parse($shift->event_start_day),
                Carbon::parse($shift->event_end_day),
                $shift->start,
                $shift->end,
                $shift->event->project->events()->get('id')->pluck('id')->toArray()
            );
    }

    public function handleShiftUsersShiftCount(Shift $shift, int $userId): void
    {
        $collidingShiftUserPivots = [];

        /** @var Shift $possiblyCollidingShift */
        foreach ($this->getPossiblyCollidingShifts($shift) as $possiblyCollidingShift) {
            $possiblyCollidingShiftUsers = $possiblyCollidingShift->users;

            if ($possiblyCollidingShiftUsers->isEmpty()) {
                continue;
            }

            foreach ($possiblyCollidingShiftUsers as $possiblyCollidingShiftUser) {
                if ($possiblyCollidingShiftUser->id !== $userId) {
                    continue;
                }
                $collidingShiftUserPivots[] = $possiblyCollidingShiftUser->pivot;
            }
        }

        $collidingShiftUserPivotsCount = count($collidingShiftUserPivots);
        foreach ($collidingShiftUserPivots as $shiftUserPivot) {
            if ($shiftUserPivot->shift_count !== $collidingShiftUserPivotsCount) {
                $this->shiftUserRepository->update(
                    $shiftUserPivot,
                    ['shift_count' => $collidingShiftUserPivotsCount]
                );
            }
        }
    }

    public function handleShiftFreelancersShiftCount(Shift $shift, int $freelancerId): void
    {
        $collidingShiftFreelancerPivots = [];

        /** @var Shift $possiblyCollidingShift */
        foreach ($this->getPossiblyCollidingShifts($shift) as $possiblyCollidingShift) {
            $possiblyCollidingShiftFreelancers = $possiblyCollidingShift->freelancer;

            if ($possiblyCollidingShiftFreelancers->isEmpty()) {
                continue;
            }

            foreach ($possiblyCollidingShiftFreelancers as $possiblyCollidingShiftFreelancer) {
                if ($possiblyCollidingShiftFreelancer->id !== $freelancerId) {
                    continue;
                }
                $collidingShiftFreelancerPivots[] = $possiblyCollidingShiftFreelancer->pivot;
            }
        }

        $collidingShiftFreelancerPivotsCount = count($collidingShiftFreelancerPivots);
        foreach ($collidingShiftFreelancerPivots as $shiftFreelancerPivot) {
            if ($shiftFreelancerPivot->shift_count !== $collidingShiftFreelancerPivotsCount) {
                $this->shiftFreelancerRepository->update(
                    $shiftFreelancerPivot,
                    ['shift_count' => $collidingShiftFreelancerPivotsCount]
                );
            }
        }
    }

    public function handleShiftServiceProvidersShiftCount(Shift $shift, int $serviceProviderId): void
    {
        $collidingShiftServiceProviderPivots = [];

        /** @var Shift $possiblyCollidingShift */
        foreach ($this->getPossiblyCollidingShifts($shift) as $possiblyCollidingShift) {
            $possiblyCollidingShiftServiceProviders = $possiblyCollidingShift->serviceProvider;

            if ($possiblyCollidingShiftServiceProviders->isEmpty()) {
                continue;
            }

            foreach ($possiblyCollidingShiftServiceProviders as $possiblyCollidingShiftServiceProvider) {
                if ($possiblyCollidingShiftServiceProvider->id !== $serviceProviderId) {
                    continue;
                }
                $collidingShiftServiceProviderPivots[] = $possiblyCollidingShiftServiceProvider->pivot;
            }
        }

        $collidingShiftServiceProviderPivotsCount = count($collidingShiftServiceProviderPivots);
        foreach ($collidingShiftServiceProviderPivots as $shiftServiceProviderPivot) {
            if ($shiftServiceProviderPivot->shift_count !== $collidingShiftServiceProviderPivotsCount) {
                $this->shiftServiceProviderRepository->update(
                    $shiftServiceProviderPivot,
                    ['shift_count' => $collidingShiftServiceProviderPivotsCount]
                );
            }
        }
    }
}
