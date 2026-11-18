<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Project\Services\ProjectTeamNotificationService;
use Illuminate\Auth\AuthManager;

class ShiftUserService
{
    public function __construct(
        private readonly ShiftRepository $shiftRepository,
        private readonly ShiftUserRepository $shiftUserRepository,
        private readonly ShiftFreelancerRepository $shiftFreelancerRepository,
        private readonly ShiftServiceProviderRepository $shiftServiceProviderRepository,
        private readonly ShiftWorkerRepository $shiftWorkerRepository,
        private readonly ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftWorkerService $shiftWorkerService,
        protected AuthManager $auth,
        private readonly ProjectTeamNotificationService $projectTeamNotificationService,
    ) {
    }

    /**
     * User einer Schicht zuweisen (inkl. Serienlogik).
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring
    public function assignToShift(
        Shift $shift,
        int $userId,
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

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $this->shiftWorkerService->assignToShift(
            $shift,
            $user,
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
     * Entfernt einen User aus einer Schicht (inkl. Serienlogik).
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring
    public function removeFromShift(
        ShiftUser|int $usersPivot,
        bool $removeFromSingleShift,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        if (is_int($usersPivot)) {
            $shiftWorkerPivot = \Artwork\Modules\Shift\Models\ShiftWorker::find($usersPivot);
            if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === User::class) {
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

        // Fallback: Alte Struktur (ShiftUser)
        $shiftUserPivot = ! $usersPivot instanceof ShiftUser
            ? $this->shiftUserRepository->getById($usersPivot)
            : $usersPivot;

        if (! $shiftUserPivot) {
            return;
        }

        if (!$shiftUserPivot->relationLoaded('shift')) {
            $shiftUserPivot->load('shift');
        }
        if (!$shiftUserPivot->relationLoaded('user')) {
            $shiftUserPivot->load('user');
        }

        $shiftWorkerPivot = $this->shiftWorkerService->convertShiftUserToShiftWorker($shiftUserPivot);
        if (!$shiftWorkerPivot) {
            // Fallback: Wenn kein ShiftWorker gefunden, lösche direkt aus alter Tabelle
            $this->forceDelete($shiftUserPivot);
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

    public function getShiftByUserPivotId(int $usersPivot): Shift
    {

        $shiftWorkerPivot = ShiftWorker::find($usersPivot);
        if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === User::class) {
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

        // Fallback: Alte Struktur (ShiftUser)
        $shiftUserPivot = ! $usersPivot instanceof ShiftUser
            ? $this->shiftUserRepository->getById($usersPivot)
            : $usersPivot;

        if (!$shiftUserPivot) {
            throw new \RuntimeException("ShiftUser pivot with ID {$usersPivot} not found");
        }


        if (!$shiftUserPivot->relationLoaded('shift')) {
            $shiftUserPivot->load('shift');
        }

        $shift = $shiftUserPivot->shift;
        if (!$shift) {
            throw new \RuntimeException(
                "Shift for ShiftUser pivot ID {$usersPivot} not found (shift_id: {$shiftUserPivot->shift_id})"
            );
        }

        return $shift;
    }

    /**
     * Alle User aus einer Schicht entfernen (inkl. Notifications).
     */
    public function removeAllUsersFromShift(
        Shift $shift,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $shift->users()->each(function (User $user) use (
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        ): void {
            $this->removeFromShift(
                $user->pivot,
                true,
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        });
    }

    public function removeFromShiftByUserIdAndShiftId(
        int $userId,
        int $shiftId,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $pivot = $this->shiftUserRepository->findByUserIdAndShiftId($userId, $shiftId);

        if (! $pivot instanceof ShiftUser) {
            // Check in shift_workers table as well
            $shiftWorker = $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
                User::class,
                $userId,
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

    public function delete(ShiftUser $shiftUser): bool
    {
        return $this->shiftUserRepository->delete($shiftUser);
    }

    public function forceDelete(ShiftUser $shiftUser): bool
    {
        return $this->shiftUserRepository->forceDelete($shiftUser);
    }

    public function restore(ShiftUser $shiftUser): bool
    {
        return $this->shiftUserRepository->restore($shiftUser);
    }

    /**
     * Logging von Änderungen an Schicht-Zuweisungen nach Commit.
     */
    protected function logCommittedShiftAssignmentChange(
        Shift $shift,
        User $user,
        string $changeType,
        ?ShiftUser $pivot = null
    ): void {
        $shiftWorker = $pivot ? $this->convertShiftUserToShiftWorker($pivot) : null;
        $this->shiftWorkerService->logCommittedShiftAssignmentChange(
            $shift,
            $user,
            $changeType,
            User::class,
            $shiftWorker
        );
    }

    private function convertShiftUserToShiftWorker(?ShiftUser $shiftUser): ?ShiftWorker
    {
        if (! $shiftUser) {
            return null;
        }

        return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
            User::class,
            $shiftUser->user_id,
            $shiftUser->shift_id
        );
    }
}
