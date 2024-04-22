<?php

namespace Artwork\Modules\Shift\Services;

use App\Models\Freelancer;
use App\Support\Services\NotificationService;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;

readonly class ShiftFreelancerService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftUserRepository $shiftUserRepository,
        private ShiftFreelancerRepository $shiftFreelancerRepository,
        private ShiftServiceProviderRepository $shiftServiceProviderRepository,
        private ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private ShiftCountService $shiftCountService,
        private VacationConflictService $vacationConflictService,
        private AvailabilityConflictService $availabilityConflictService,
        private ChangeService $changeService
    ) {
    }

    public function assignToShift(
        Shift $shift,
        int $freelancerId,
        int $shiftQualificationId,
        NotificationService $notificationService,
        array|null $seriesShiftData = null,
    ): void {
        $shiftFreelancerPivot = $this->shiftFreelancerRepository->createForShift(
            $shift->id,
            $freelancerId,
            $shiftQualificationId
        );

        $this->shiftCountService->handleShiftFreelancersShiftCount($shift, $freelancerId);

        if ($shift->is_committed) {
            $this->handleAssignedToShift(
                $shift,
                $shiftFreelancerPivot->freelancer,
                $shiftFreelancerPivot->shiftQualification,
                $notificationService
            );
        }

        if (
            $seriesShiftData !== null &&
            isset($seriesShiftData['onlyThisDay']) &&
            $seriesShiftData['onlyThisDay'] === false
        ) {
            $this->handleSeriesShiftData(
                $shift,
                Carbon::parse($seriesShiftData['start'])->startOfDay(),
                Carbon::parse($seriesShiftData['end'])->endOfDay(),
                $seriesShiftData['dayOfWeek'],
                $freelancerId,
                $shiftQualificationId,
                $notificationService
            );
        }
    }

    private function handleAssignedToShift(
        Shift $shift,
        Freelancer $freelancer,
        ShiftQualification $shiftQualification,
        NotificationService $notificationService
    ): void {
        $this->createAssignedToShiftHistoryEntry($shift, $freelancer, $shiftQualification);
        $this->checkVacationConflicts($shift, $freelancer, $notificationService);
        $this->checkAvailabilityConflicts($shift, $freelancer);
    }

    private function createAssignedToShiftHistoryEntry(
        Shift $shift,
        Freelancer $freelancer,
        ShiftQualification $shiftQualification
    ): void {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('shift')
                ->setModelClass(Shift::class)
                ->setModelId($shift->id)
                ->setShift($shift)
                ->setTranslationKey('Freelancer was added to the shift as')
                ->setTranslationKeyPlaceholderValues([
                    $freelancer->getNameAttribute(),
                    $shift->craft->abbreviation,
                    $shift->event->eventName,
                    $shiftQualification->name
                ])
        );
    }

    private function handleSeriesShiftData(
        Shift $shift,
        Carbon $start,
        Carbon $end,
        string $dayOfWeek,
        int $freelancerId,
        int $shiftQualificationId,
        NotificationService $notificationService
    ): void {
        /** @var Shift $shiftBetweenDates */
        foreach (
            $this->shiftRepository->getShiftsByUuidBetweenDates($shift->shift_uuid, $start, $end) as $shiftBetweenDates
        ) {
            if (
                //same shift is found, user already assigned, continue
                $shiftBetweenDates->id === $shift->id ||
                //if day of week is given and is not "all" compare it to shift, if not matching continue
                (
                    $dayOfWeek !== 'all' &&
                    Carbon::parse($shiftBetweenDates->event_start_day)->dayOfWeek !== ((int) $dayOfWeek)
                ) ||
                //if user already assigned to shift continue
                $shiftBetweenDates->freelancer()
                    ->get(['freelancers.id'])
                    ->pluck('id')
                    ->contains($freelancerId)
            ) {
                continue;
            }

            //get value of shifts qualifications by shiftQualificationId and shiftId to determine how many users
            //can be assigned in total
            $shiftsQualificationsValue = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
                $shiftBetweenDates->id,
                $shiftQualificationId
            )?->value;

            //if shiftsQualifications value is null or 0 continue
            if ($shiftsQualificationsValue === null || $shiftsQualificationsValue === 0) {
                continue;
            }

            //determine if a slot is available, get all shift_user, shifts_freelancers and shifts_service_providers
            //entries containing shiftId and shiftQualificationId and count them
            if (
                $this->getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
                    $shiftBetweenDates->id,
                    $shiftQualificationId
                ) < $shiftsQualificationsValue
            ) {
                //call assignToShift without seriesShiftData to make sure only this user is assigned to shift and same
                //logic is applied for each user
                $this->assignToShift(
                    $shiftBetweenDates,
                    $freelancerId,
                    $shiftQualificationId,
                    $notificationService
                );
            }
        }
    }

    private function getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
        int $shiftId,
        int $shiftQualificationId
    ): int {
        return $this->shiftUserRepository->getCountForShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        ) + $this->shiftFreelancerRepository->getCountForShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        ) + $this->shiftServiceProviderRepository->getCountForShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );
    }

    public function removeFromShift(
        ShiftFreelancer|int $freelancersPivot,
        bool $removeFromSingleShift,
        NotificationService $notificationService
    ): void {
        $shiftFreelancerPivot = !$freelancersPivot instanceof ShiftFreelancer ?
            $this->shiftFreelancerRepository->getById($freelancersPivot) :
            $freelancersPivot;

        /** @var Shift $shift */
        $shift = $shiftFreelancerPivot->shift;
        /** @var Freelancer $freelancer */
        $freelancer = $shiftFreelancerPivot->freelancer;

        $this->forceDelete($shiftFreelancerPivot);
        $this->shiftCountService->handleShiftFreelancersShiftCount($shift, $freelancer->id);

        if ($shift->is_committed) {
            $this->handleRemovedFromShift($shift, $freelancer, $notificationService);
        }

        if (!$removeFromSingleShift) {
            foreach ($this->shiftRepository->getShiftsByUuid($shift->shift_uuid) as $shiftByUuid) {
                if ($shiftByUuid->id === $shift->id) {
                    continue;
                }

                //find additional shift freelancer pivot by shift and given freelancer id, if found call this function
                //again with removeFromSingleShift set to true making sure same logic is applied for each
                //pivot which is deleted
                $shiftFreelancerPivotByUuid = $this->shiftRepository->getShiftFreelancerPivotById(
                    $shiftByUuid,
                    $freelancer->id
                );
                if ($shiftFreelancerPivotByUuid instanceof ShiftFreelancer) {
                    $this->removeFromShift($shiftFreelancerPivotByUuid, true, $notificationService);
                }
            }
        }
    }

    public function removeAllFreelancersFromShift(Shift $shift, NotificationService $notificationService): void
    {
        $shift->freelancer()->each(
            function (Freelancer $freelancer) use ($notificationService): void {
                $this->removeFromShift($freelancer->pivot, true, $notificationService);
            }
        );
    }

    public function removeFromShiftByUserIdAndShiftId(
        int $freelancerId,
        int $shiftId,
        NotificationService $notificationService
    ): void {
        $this->removeFromShift(
            $this->shiftFreelancerRepository->findByUserIdAndShiftId(
                $freelancerId,
                $shiftId
            ),
            true,
            $notificationService
        );
    }

    private function handleRemovedFromShift(
        Shift $shift,
        Freelancer $freelancer,
        NotificationService $notificationService
    ): void {
        $this->createRemovedFromShiftHistoryEntry($shift, $freelancer);
        $this->checkVacationConflicts($shift, $freelancer, $notificationService);
        $this->checkAvailabilityConflicts($shift, $freelancer);
    }

    private function createRemovedFromShiftHistoryEntry(Shift $shift, Freelancer $freelancer): void
    {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('shift')
                ->setModelClass(Shift::class)
                ->setModelId($shift->id)
                ->setShift($shift)
                ->setTranslationKey('Freelancer was removed from shift')
                ->setTranslationKeyPlaceholderValues([
                    $freelancer->getNameAttribute(),
                    $shift->craft->abbreviation,
                    $shift->event->eventName
                ])
        );
    }

    private function checkVacationConflicts(
        Shift $shift,
        Freelancer $freelancer,
        NotificationService $notificationService
    ): void {
        $this->vacationConflictService->checkVacationConflictsShifts(
            $shift,
            $notificationService,
            null,
            $freelancer
        );
    }

    private function checkAvailabilityConflicts(Shift $shift, Freelancer $freelancer): void
    {
        $this->availabilityConflictService->checkAvailabilityConflictsShifts($shift, null, $freelancer);
    }

    public function delete(ShiftFreelancer $shiftFreelancer): bool
    {
        return $this->shiftFreelancerRepository->delete($shiftFreelancer);
    }

    public function forceDelete(ShiftFreelancer $shiftFreelancer): bool
    {
        return $this->shiftFreelancerRepository->forceDelete($shiftFreelancer);
    }

    public function restore(ShiftFreelancer $shiftFreelancer): bool
    {
        return $this->shiftFreelancerRepository->restore($shiftFreelancer);
    }
}
