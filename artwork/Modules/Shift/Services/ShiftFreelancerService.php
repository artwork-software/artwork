<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Vacation\Services\VacationConflictService;
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
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring
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
        bool $isOverbooked = false
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
            $seriesShiftData,
            $isOverbooked
        );
    }





    /**
     * Freelancer aus Schicht entfernen (inkl. Serienlogik).
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring
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

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring

    public function getShiftByUserPivotId(int $usersPivot): Shift
    {
        $shiftWorkerPivot = \Artwork\Modules\Shift\Models\ShiftWorker::find($usersPivot);
        if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === Freelancer::class) {
            if (!$shiftWorkerPivot->relationLoaded('shift')) {
                $shiftWorkerPivot->load('shift');
            }

            $shift = $shiftWorkerPivot->shift;
            if (!$shift) {
                throw new \RuntimeException(
                    "Shift for ShiftWorker pivot ID {$usersPivot} not found (shift_id: {$shiftWorkerPivot->shift_id})"
                );
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
            throw new \RuntimeException(
                "Shift for ShiftFreelancer pivot ID {$usersPivot} not found "
                . "(shift_id: {$shiftFreelancerPivot->shift_id})"
            );
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
            // Check in shift_workers table as well
            $shiftWorker = $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
                Freelancer::class,
                $freelancerId,
                $shiftId
            );
            if ($shiftWorker instanceof ShiftWorker) {
                if (!$shiftWorker->relationLoaded('shift')) {
                    $shiftWorker->load('shift');
                }
                $this->shiftWorkerService->removeFromShift(
                    $shiftWorker,
                    true,
                    $notificationService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
            }
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
}
