<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;

readonly class ShiftFreelancerService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftUserRepository $shiftUserRepository,
        private ShiftFreelancerRepository $shiftFreelancerRepository,
        private ShiftServiceProviderRepository $shiftServiceProviderRepository,
        private ShiftWorkerRepository $shiftWorkerRepository,
        private ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private ShiftsQualificationsService $shiftsQualificationsService,
        private ShiftWorkerService $shiftWorkerService,
        protected AuthManager $auth,
    ) {
    }

    /**
     * Freelancer einer Schicht zuweisen (inkl. Serienlogik).
     */
    public function assignToShift(
        Shift $shift,
        int $freelancerId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService,
        ?array $seriesShiftData = null,
    ): void {

        $freelancer = Freelancer::find($freelancerId);
        if (!$freelancer) {
            return;
        }

        $this->shiftWorkerService->assignToShift(
            $shift,
            $freelancer,
            $shiftQualificationId,
            $craftAbbreviation,
            $notificationService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService,
            $seriesShiftData
        );
    }

    private function isFreelancerAlreadyAssignedToShift(Shift $shift, int $freelancerId): bool
    {
        return $shift->freelancer()
            ->get(['freelancers.id'])
            ->pluck('id')
            ->contains($freelancerId);
    }


    private function handleAssignedToShift(
        Shift $shift,
        Freelancer $freelancer,
        ShiftQualification $shiftQualification,
        NotificationService $notificationService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        if ($shift?->event?->exists) {
            $changeService->saveFromBuilder(
                $changeService
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
                        $shiftQualification->name,
                    ])
            );
        }

        $vacationConflictService->checkVacationConflictsShifts(
            $shift,
            $notificationService,
            null,
            $freelancer
        );
        $availabilityConflictService->checkAvailabilityConflictsShifts(
            $shift,
            $notificationService,
            null,
            $freelancer
        );
    }

    private function handleSeriesShiftData(
        Shift $shift,
        Carbon $start,
        Carbon $end,
        string $dayOfWeek,
        int $freelancerId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        /** @var Shift $shiftBetweenDates */
        foreach (
            $this->shiftRepository->getShiftsByUuidBetweenDates($shift->shift_uuid, $start, $end) as $shiftBetweenDates
        ) {
            if (
                $this->shiftWorkerService->isSameShift($shift, $shiftBetweenDates) ||
                $this->shiftWorkerService->isDayOfWeekFilteredOut($dayOfWeek, $shiftBetweenDates) ||
                $this->isFreelancerAlreadyAssignedToShift($shiftBetweenDates, $freelancerId)
            ) {
                continue;
            }

            $shiftsQualificationsValue = $this->shiftsQualificationsRepository
                ->findByShiftIdAndShiftQualificationId($shiftBetweenDates->id, $shiftQualificationId)?->value;

            if ($shiftsQualificationsValue === null || $shiftsQualificationsValue === 0) {
                continue;
            }

            if (
                $this->shiftWorkerService->getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
                    $shiftBetweenDates->id,
                    $shiftQualificationId
                ) >= $shiftsQualificationsValue
            ) {
                continue;
            }

            // Nur für diese Schicht zuweisen (ohne Serienlogik erneut anzustoßen)
            $this->assignToShift(
                $shiftBetweenDates,
                $freelancerId,
                $shiftQualificationId,
                $craftAbbreviation,
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }
    }


    /**
     * Freelancer aus Schicht entfernen (inkl. Serienlogik).
     */
    public function removeFromShift(
        ShiftFreelancer|int $freelancersPivot,
        bool $removeFromSingleShift,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        if (is_int($freelancersPivot)) {
            $shiftWorkerPivot = \Artwork\Modules\Shift\Models\ShiftWorker::find($freelancersPivot);
            if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === Freelancer::class) {
                if (!$shiftWorkerPivot->relationLoaded('shift')) {
                    $shiftWorkerPivot->load('shift');
                }

                $this->shiftWorkerService->removeFromShift(
                    $shiftWorkerPivot,
                    $removeFromSingleShift,
                    $notificationService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
                return;
            }
        }

        // Fallback: Alte Struktur (ShiftFreelancer)
        $shiftFreelancerPivot = ! $freelancersPivot instanceof ShiftFreelancer
            ? $this->shiftFreelancerRepository->getById($freelancersPivot)
            : $freelancersPivot;

        if (! $shiftFreelancerPivot instanceof ShiftFreelancer) {
            return;
        }

        if (!$shiftFreelancerPivot->relationLoaded('shift')) {
            $shiftFreelancerPivot->load('shift');
        }
        if (!$shiftFreelancerPivot->relationLoaded('freelancer')) {
            $shiftFreelancerPivot->load('freelancer');
        }

        $shiftWorkerPivot = $this->shiftWorkerService->convertShiftFreelancerToShiftWorker($shiftFreelancerPivot);
        if (!$shiftWorkerPivot) {
            // Fallback: Wenn kein ShiftWorker gefunden, lösche direkt aus alter Tabelle
            $this->forceDelete($shiftFreelancerPivot);
            return;
        }

        if (!$shiftWorkerPivot->relationLoaded('shift')) {
            $shiftWorkerPivot->load('shift');
        }

        $this->shiftWorkerService->removeFromShift(
            $shiftWorkerPivot,
            $removeFromSingleShift,
            $notificationService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );
    }

    private function removeFreelancerFromAllShiftsWithSameUuid(
        Shift $shift,
        Freelancer $freelancer,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        foreach ($this->shiftRepository->getShiftsByUuid($shift->shift_uuid) as $shiftByUuid) {
            if ($shiftByUuid->id === $shift->id) {
                continue;
            }

            $shiftFreelancerPivotByUuid = $this->shiftRepository->getShiftWorkerPivotById(
                $shiftByUuid,
                Freelancer::class,
                $freelancer->id
            );

            if ($shiftFreelancerPivotByUuid instanceof ShiftWorker) {
                $this->shiftWorkerService->removeFromShift(
                    $shiftFreelancerPivotByUuid,
                    true,
                    $notificationService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
            }
        }
    }

    public function getShiftByUserPivotId(int $usersPivot): Shift
    {
        $shiftWorkerPivot = \Artwork\Modules\Shift\Models\ShiftWorker::find($usersPivot);
        if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === Freelancer::class) {
            if (!$shiftWorkerPivot->relationLoaded('shift')) {
                $shiftWorkerPivot->load('shift');
            }

            $shift = $shiftWorkerPivot->shift;
            if (!$shift) {
                throw new \RuntimeException("Shift for ShiftWorker pivot ID {$usersPivot} not found (shift_id: {$shiftWorkerPivot->shift_id})");
            }

            return $shift;
        }

        // Fallback: Alte Struktur (ShiftFreelancer)
        $shiftFreelancerPivot = ! $usersPivot instanceof ShiftFreelancer
            ? $this->shiftFreelancerRepository->getById($usersPivot)
            : $usersPivot;

        if (!$shiftFreelancerPivot) {
            throw new \RuntimeException("ShiftFreelancer pivot with ID {$usersPivot} not found");
        }

        if (!$shiftFreelancerPivot->relationLoaded('shift')) {
            $shiftFreelancerPivot->load('shift');
        }

        $shift = $shiftFreelancerPivot->shift;
        if (!$shift) {
            throw new \RuntimeException("Shift for ShiftFreelancer pivot ID {$usersPivot} not found (shift_id: {$shiftFreelancerPivot->shift_id})");
        }

        return $shift;
    }

    public function removeAllFreelancersFromShift(
        Shift $shift,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $shift->freelancer()->each(
            function (Freelancer $freelancer) use (
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            ): void {
                $this->removeFromShift(
                    $freelancer->pivot,
                    true,
                    $notificationService,
                    $shiftCountService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
            }
        );
    }

    public function removeFromShiftByUserIdAndShiftId(
        int $freelancerId,
        int $shiftId,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $pivot = $this->shiftFreelancerRepository->findByFreelancerIdAndShiftId(
            $freelancerId,
            $shiftId
        );

        if (! $pivot instanceof ShiftFreelancer) {
            return;
        }

        $this->removeFromShift(
            $pivot,
            true,
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );
    }

    private function handleRemovedFromShift(
        Shift $shift,
        Freelancer $freelancer,
        NotificationService $notificationService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        if ($shift?->event?->exists) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Freelancer was removed from shift')
                    ->setTranslationKeyPlaceholderValues([
                        $freelancer->getNameAttribute(),
                        $shift->craft->abbreviation,
                        $shift->event->eventName,
                    ])
            );
        }

        $vacationConflictService->checkVacationConflictsShifts(
            $shift,
            $notificationService,
            null,
            $freelancer
        );
        $availabilityConflictService->checkAvailabilityConflictsShifts(
            $shift,
            $notificationService,
            null,
            $freelancer
        );
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

    /**
     * Logging von Änderungen an Schicht-Zuweisungen nach Commit.
     */
    protected function logCommittedShiftAssignmentChange(
        Shift $shift,
        Freelancer $freelancer,
        string $changeType,
        ?ShiftFreelancer $pivot = null
    ): void {
        $shiftWorker = $pivot ? $this->convertShiftFreelancerToShiftWorker($pivot) : null;
        $this->shiftWorkerService->logCommittedShiftAssignmentChange(
            $shift,
            $freelancer,
            $changeType,
            Freelancer::class,
            $shiftWorker
        );
    }

    private function convertShiftFreelancerToShiftWorker(?ShiftFreelancer $shiftFreelancer): ?ShiftWorker
    {
        if (! $shiftFreelancer) {
            return null;
        }

        return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
            Freelancer::class,
            $shiftFreelancer->freelancer_id,
            $shiftFreelancer->shift_id
        );
    }

    private function logManualRemovalActivity(Shift $shift, ShiftFreelancer $shiftFreelancerPivot): void
    {
        if (! $shift->is_committed && ! $shift->in_workflow) {
            return;
        }

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event('removed')
            ->tap(function ($activity) use ($shift, $shiftFreelancerPivot): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => '{0} removed from shift as {1} for {2} ({3})',
                    'translation_key_placeholder_values' => [
                        $shiftFreelancerPivot->freelancer->name,
                        $shiftFreelancerPivot->shiftQualification->name,
                        $shift->craft->name,
                        $shiftFreelancerPivot->craft_abbreviation,
                    ],
                ]);
            })
            ->log('Freelancer removed from shift');
    }

    private function logManualAssignmentActivity(Shift $shift, ShiftFreelancer $shiftFreelancerPivot): void
    {
        if (! $shift->is_committed && ! $shift->in_workflow) {
            return;
        }

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event('assigned')
            ->tap(function ($activity) use ($shift, $shiftFreelancerPivot): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
                    'translation_key_placeholder_values' => [
                        $shiftFreelancerPivot->freelancer->name,
                        $shiftFreelancerPivot->shiftQualification->name,
                        $shift->craft->name,
                        $shiftFreelancerPivot->craft_abbreviation,
                    ],
                ]);
            })
            ->log('Freelancer assigned to shift');
    }
}
