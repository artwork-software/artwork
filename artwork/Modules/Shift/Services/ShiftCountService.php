<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Collection;

readonly class ShiftCountService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftWorkerRepository $shiftWorkerRepository
    ) {
    }

    private function getPossiblyCollidingShifts(Shift $shift): Collection|null
    {
        return $this
            ->shiftRepository
            ->getShiftsBetweenEventStartDayAndEventEndDayStartAndEndTimeOverlapByProjectEventIds(
                Carbon::parse($shift->event_start_day),
                Carbon::parse($shift->event_end_day),
                $shift->start,
                $shift->end,
                $shift->event?->project?->events()->get('id')->pluck('id')->toArray() ?? []
            );
    }

    public function handleShiftUsersShiftCount(Shift $shift, int $userId): void
    {
        $this->handleShiftWorkerCount($shift, User::class, $userId);
    }

    public function handleShiftFreelancersShiftCount(Shift $shift, int $freelancerId): void
    {
        $this->handleShiftWorkerCount($shift, Freelancer::class, $freelancerId);
    }

    public function handleShiftServiceProvidersShiftCount(Shift $shift, int $serviceProviderId): void
    {
        $this->handleShiftWorkerCount($shift, ServiceProvider::class, $serviceProviderId);
    }

    private function handleShiftWorkerCount(Shift $shift, string $employableType, int $employableId): void
    {
        $collidingShiftWorkerPivots = [];

        /** @var Shift $possiblyCollidingShift */
        foreach ($this->getPossiblyCollidingShifts($shift) as $possiblyCollidingShift) {
            $shiftWorker = $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
                $employableType,
                $employableId,
                $possiblyCollidingShift->id
            );

            if ($shiftWorker) {
                $collidingShiftWorkerPivots[] = $shiftWorker;
            }
        }

        $collidingShiftWorkerPivotsCount = count($collidingShiftWorkerPivots);
        foreach ($collidingShiftWorkerPivots as $shiftWorker) {
            if ($shiftWorker->shift_count !== $collidingShiftWorkerPivotsCount) {
                $shiftWorker->update(['shift_count' => $collidingShiftWorkerPivotsCount]);
            }
        }
    }
}
