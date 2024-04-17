<?php

namespace Artwork\Modules\Shift\Services;

use App\Models\ServiceProvider;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Carbon\Carbon;

readonly class ShiftServiceProviderService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftUserRepository $shiftUserRepository,
        private ShiftFreelancerRepository $shiftFreelancerRepository,
        private ShiftServiceProviderRepository $shiftServiceProviderRepository,
        private ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private ShiftCountService $shiftCountService,
        private ChangeService $changeService
    ) {
    }

    public function assignToShift(
        Shift $shift,
        int $serviceProviderId,
        int $shiftQualificationId,
        array|null $seriesShiftData = null
    ): void {
        $shiftServiceProviderPivot = $this->shiftServiceProviderRepository->createForShift(
            $shift->id,
            $serviceProviderId,
            $shiftQualificationId
        );

        $this->shiftCountService->handleShiftServiceProvidersShiftCount($shift, $serviceProviderId);

        if ($shift->is_committed) {
            $this->createAssignedToShiftHistoryEntry(
                $shift,
                $shiftServiceProviderPivot->serviceProvider,
                $shiftServiceProviderPivot->shiftQualification
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
                $serviceProviderId,
                $shiftQualificationId
            );
        }
    }

    private function createAssignedToShiftHistoryEntry(
        Shift $shift,
        ServiceProvider $serviceProvider,
        ShiftQualification $shiftQualification
    ): void {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('shift')
                ->setModelClass(Shift::class)
                ->setModelId($shift->id)
                ->setShift($shift)
                ->setTranslationKey('Service provider was added to the shift as')
                ->setTranslationKeyPlaceholderValues([
                    $serviceProvider->getNameAttribute(),
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
        int $serviceProviderId,
        int $shiftQualificationId
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
                $shiftBetweenDates->serviceProvider()
                    ->get(['service_providers.id'])
                    ->pluck('id')
                    ->contains($serviceProviderId)
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
                $this->assignToShift($shiftBetweenDates, $serviceProviderId, $shiftQualificationId, null);
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
        ShiftServiceProvider|int $serviceProvidersPivot,
        bool $removeFromSingleShift
    ): void {
        $shiftServiceProviderPivot = !$serviceProvidersPivot instanceof ShiftServiceProvider ?
            $this->shiftServiceProviderRepository->getById($serviceProvidersPivot) :
            $serviceProvidersPivot;

        /** @var Shift $shift */
        $shift = $shiftServiceProviderPivot->shift;
        /** @var ServiceProvider $serviceProvider */
        $serviceProvider = $shiftServiceProviderPivot->serviceProvider;

        $this->forceDelete($shiftServiceProviderPivot);

        $this->shiftCountService->handleShiftServiceProvidersShiftCount($shift, $serviceProvider->id);

        if ($shift->is_committed) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Service provider was removed from shift')
                    ->setTranslationKeyPlaceholderValues([
                        $serviceProvider->getNameAttribute(),
                        $shift->craft->abbreviation,
                        $shift->event->eventName
                    ])
            );
        }

        if (!$removeFromSingleShift) {
            foreach ($this->shiftRepository->getShiftsByUuid($shift->shift_uuid) as $shiftByUuid) {
                if ($shiftByUuid->id === $shift->id) {
                    continue;
                }

                //find additional shift freelancer pivot by shift and given freelancer id, if found call this function
                //again with removeFromSingleShift set to true making sure same logic is applied for each
                //pivot which is deleted
                $shiftServiceProviderPivotByUuid = $this->shiftRepository->getShiftServiceProviderPivotById(
                    $shiftByUuid,
                    $serviceProvider->id
                );
                if ($shiftServiceProviderPivotByUuid instanceof ShiftServiceProvider) {
                    $this->removeFromShift($shiftServiceProviderPivotByUuid, true);
                }
            }
        }
    }

    public function removeAllServiceProvidersFromShift(Shift $shift): void
    {
        $shift->serviceProvider()->each(
            function (ServiceProvider $serviceProvider): void {
                $this->removeFromShift($serviceProvider->pivot, true);
            }
        );
    }

    public function removeFromShiftByUserIdAndShiftId(int $freelancerId, int $shiftId): void
    {
        $this->removeFromShift(
            $this->shiftServiceProviderRepository->findByUserIdAndShiftId(
                $freelancerId,
                $shiftId
            ),
            true
        );
    }

    public function delete(ShiftServiceProvider $shiftServiceProvider): bool
    {
        return $this->shiftServiceProviderRepository->delete($shiftServiceProvider);
    }

    public function forceDelete(ShiftServiceProvider $shiftServiceProvider): bool
    {
        return $this->shiftServiceProviderRepository->forceDelete($shiftServiceProvider);
    }

    public function restore(ShiftServiceProvider $shiftServiceProvider): bool
    {
        return $this->shiftServiceProviderRepository->restore($shiftServiceProvider);
    }
}
