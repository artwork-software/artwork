<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Database\Traits\ReceivesNewHistoryServiceTrait;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class ShiftService
{
    use ReceivesNewHistoryServiceTrait;

    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftsQualificationsService $shiftsQualificationsService,
        private ShiftUserService $shiftUserService,
        private ShiftFreelancerService $shiftFreelancerService,
        private ShiftServiceProviderService $shiftServiceProviderService
    ) {
    }

    public function getById(int $shiftId): Shift|null
    {
        return $this->shiftRepository->getById($shiftId);
    }

    public function createFromShiftPresetShiftForEvent(PresetShift $presetShift, int $eventId): Shift
    {
        $shift = new Shift([
            'event_id' => $eventId,
            'start' => $presetShift->start,
            'end' => $presetShift->end,
            'break_minutes' => $presetShift->break_minutes,
            'craft_id' => $presetShift->craft_id,
            'description' => $presetShift->description,
            'is_committed' => false
        ]);

        $this->shiftRepository->save($shift);
        return $shift;
    }

    public function createRemovedAllUsersFromShiftHistoryEntry(Shift $shift): void
    {
        $this->getNewHistoryService(Shift::class)->createHistory(
            $shift->id,
            'All scheduled employees have been removed from shift',
            [
                $shift->craft->abbreviation,
                $shift->event->eventName
            ],
            'shift'
        );
    }

    public function delete(Shift $shift): bool
    {
        foreach ($shift->shiftsQualifications as $shiftsQualification) {
            $this->shiftsQualificationsService->delete($shiftsQualification);
        }

        foreach ($shift->users as $user) {
            $this->shiftUserService->delete($user->pivot);
        }

        foreach ($shift->freelancer as $freelancer) {
            $this->shiftFreelancerService->delete($freelancer->pivot);
        }

        foreach ($shift->serviceProvider as $serviceProvider) {
            $this->shiftServiceProviderService->delete($serviceProvider->pivot);
        }

        return $this->shiftRepository->delete($shift);
    }

    public function deleteShifts(Collection|array $shifts): void
    {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->delete($shift);
        }
    }

    public function restoreShifts(Collection|array $shifts): void
    {

        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $shift->restore();
            $shift->shiftsQualifications()->onlyTrashed()->each(
                fn($shiftsQualification) => $this->shiftsQualificationsService->restore($shiftsQualification)
            );

            // restore shift users and freelancers from pivot table
            $shift->users()->each(
                fn($user) => $this->shiftUserService->restore($user->pivot)
            );

            $shift->freelancer()->each(
                fn($freelancer) => $this->shiftFreelancerService->restore($freelancer->pivot)
            );

            $shift->serviceProvider()->each(
                fn($serviceProvider) => $this->shiftServiceProviderService->restore($serviceProvider->pivot)
            );

            /*foreach ($shift->shiftsQualifications()->onlyTrashed()->get() as $shiftsQualification) {
                $this->shiftsQualificationsService->restore($shiftsQualification);
            }*/

            /*$shift->users()->onlyTrashed()->each(
                fn($user) => $this->shiftUserService->restore($user->pivot)
            );

            $shift->freelancer()->onlyTrashed()->each(
                fn($freelancer) => $this->shiftFreelancerService->restore($freelancer->pivot)
            );

            $shift->serviceProvider()->onlyTrashed()->each(
                fn($serviceProvider) => $this->shiftServiceProviderService->restore($serviceProvider->pivot)
            );*/
        }
    }

    public function forceDelete(Shift $shift): bool
    {
        //relations are deleted on cascade
        return $this->shiftRepository->forceDelete($shift);
    }

    public function forceDeleteShifts(Collection|array $shifts): void
    {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->forceDelete($shift);
        }
    }
}
