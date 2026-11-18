<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Illuminate\Auth\AuthManager;

readonly class ShiftServiceProviderService
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
        protected AuthManager $auth
    ) {
    }

    /**
     * Service Provider einer Schicht zuweisen (inkl. Serienlogik).
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring
    public function assignToShift(
        Shift $shift,
        int $serviceProviderId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        ShiftCountService $shiftCountService,
        ChangeService $changeService,
        array|null $seriesShiftData = null,
        bool $isOverbooked = false
    ): void {

        $serviceProvider = ServiceProvider::find($serviceProviderId);
        if (!$serviceProvider) {
            return;
        }

        $this->shiftWorkerService->assignToShift(
            $shift,
            $serviceProvider,
            $shiftQualificationId,
            $craftAbbreviation,
            null, // notificationService
            null, // vacationConflictService
            null, // availabilityConflictService
            $changeService,
            $seriesShiftData,
            $isOverbooked
        );
    }




    /**
     * Service Provider aus Schicht entfernen (inkl. Serienlogik).
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring
    public function removeFromShift(
        ShiftServiceProvider|int $serviceProvidersPivot,
        bool $removeFromSingleShift,
        ShiftCountService $shiftCountService,
        ChangeService $changeService
    ): void {

        if (is_int($serviceProvidersPivot)) {
            $shiftWorkerPivot = \Artwork\Modules\Shift\Models\ShiftWorker::find($serviceProvidersPivot);
            if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === ServiceProvider::class) {
                // Direkt ShiftWorker verwenden
                if (!$shiftWorkerPivot->relationLoaded('shift')) {
                    $shiftWorkerPivot->load('shift');
                }

                $this->shiftWorkerService->removeFromShift(
                    $shiftWorkerPivot,
                    $removeFromSingleShift,
                    null, // notificationService
                    null, // vacationConflictService
                    null, // availabilityConflictService
                    $changeService
                );
                return;
            }
        }

        // Fallback: Alte Struktur (ShiftServiceProvider)
        $shiftServiceProviderPivot = ! $serviceProvidersPivot instanceof ShiftServiceProvider
            ? $this->shiftServiceProviderRepository->getById($serviceProvidersPivot)
            : $serviceProvidersPivot;

        if (! $shiftServiceProviderPivot instanceof ShiftServiceProvider) {
            return;
        }

        if (!$shiftServiceProviderPivot->relationLoaded('shift')) {
            $shiftServiceProviderPivot->load('shift');
        }
        if (!$shiftServiceProviderPivot->relationLoaded('serviceProvider')) {
            $shiftServiceProviderPivot->load('serviceProvider');
        }

        $shiftWorkerPivot = $this
            ->shiftWorkerService
            ->convertShiftServiceProviderToShiftWorker($shiftServiceProviderPivot);
        if (!$shiftWorkerPivot) {
            // Fallback: Wenn kein ShiftWorker gefunden, lösche direkt aus alter Tabelle
            $this->forceDelete($shiftServiceProviderPivot);
            return;
        }

        if (!$shiftWorkerPivot->relationLoaded('shift')) {
            $shiftWorkerPivot->load('shift');
        }

        $this->shiftWorkerService->removeFromShift(
            $shiftWorkerPivot,
            $removeFromSingleShift,
            null, // notificationService
            null, // vacationConflictService
            null, // availabilityConflictService
            $changeService
        );
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur an vielen Aufrufstellen positional – Bereinigung ist ein eigenes Refactoring

    public function getShiftByUserPivotId(int $usersPivot): Shift
    {
        $shiftWorkerPivot = \Artwork\Modules\Shift\Models\ShiftWorker::find($usersPivot);
        if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === ServiceProvider::class) {
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

        // Fallback: Alte Struktur (ShiftServiceProvider)
        $shiftServiceProviderPivot = ! $usersPivot instanceof ShiftServiceProvider
            ? $this->shiftServiceProviderRepository->getById($usersPivot)
            : $usersPivot;

        if (!$shiftServiceProviderPivot) {
            throw new \RuntimeException("ShiftServiceProvider pivot with ID {$usersPivot} not found");
        }

        if (!$shiftServiceProviderPivot->relationLoaded('shift')) {
            $shiftServiceProviderPivot->load('shift');
        }

        $shift = $shiftServiceProviderPivot->shift;
        if (!$shift) {
            throw new \RuntimeException(
                "Shift for ShiftServiceProvider pivot ID {$usersPivot} not found "
                . "(shift_id: {$shiftServiceProviderPivot->shift_id})"
            );
        }

        return $shift;
    }

    public function removeAllServiceProvidersFromShift(
        Shift $shift,
        ShiftCountService $shiftCountService,
        ChangeService $changeService
    ): void {
        $shift->serviceProvider()->each(
            function (ServiceProvider $serviceProvider) use ($shiftCountService, $changeService): void {
                $this->removeFromShift(
                    $serviceProvider->pivot,
                    true,
                    $shiftCountService,
                    $changeService
                );
            }
        );
    }

    public function removeFromShiftByUserIdAndShiftId(
        int $freelancerId,
        int $shiftId,
        ShiftCountService $shiftCountService,
        ChangeService $changeService
    ): void {
        // Param-Name ist historisch ($freelancerId), semantisch ist es ServiceProviderId – Signatur bleibt aber gleich.
        $pivot = $this->shiftServiceProviderRepository->findByServiceProviderIdAndShiftId(
            $freelancerId,
            $shiftId
        );

        if (! $pivot instanceof ShiftServiceProvider) {
            // Check in shift_workers table as well
            $shiftWorker = $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
                ServiceProvider::class,
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
                    null,
                    null,
                    null,
                    $changeService
                );
            }
            return;
        }

        $this->removeFromShift(
            $pivot,
            true,
            $shiftCountService,
            $changeService
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

    /**
     * Logging von Änderungen an Schicht-Zuweisungen nach Commit.
     */
    protected function logCommittedShiftAssignmentChange(
        Shift $shift,
        ServiceProvider $serviceProvider,
        string $changeType,
        ?ShiftServiceProvider $pivot = null
    ): void {
        $shiftWorker = $pivot ? $this->shiftWorkerService->convertShiftServiceProviderToShiftWorker($pivot) : null;
        $this->shiftWorkerService->logCommittedShiftAssignmentChange(
            $shift,
            $serviceProvider,
            $changeType,
            ServiceProvider::class,
            $shiftWorker
        );
    }
}
