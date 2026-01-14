<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
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
    public function assignToShift(
        Shift $shift,
        int $serviceProviderId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        ShiftCountService $shiftCountService,
        ChangeService $changeService,
        array|null $seriesShiftData = null
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
            $seriesShiftData
        );
    }

    private function isServiceProviderAlreadyAssignedToShift(Shift $shift, int $serviceProviderId): bool
    {
        return $shift->serviceProvider()
            ->get(['service_providers.id'])
            ->pluck('id')
            ->contains($serviceProviderId);
    }

    private function handleSeriesShiftData(
        Shift $shift,
        Carbon $start,
        Carbon $end,
        string $dayOfWeek,
        int $serviceProviderId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        ShiftCountService $shiftCountService,
        ChangeService $changeService
    ): void {
        /** @var Shift $shiftBetweenDates */
        foreach ($this->shiftRepository->getShiftsByUuidBetweenDates($shift->shift_uuid, $start, $end) as $shiftBetweenDates) {
            if (
                $this->shiftWorkerService->isSameShift($shift, $shiftBetweenDates) ||
                $this->shiftWorkerService->isDayOfWeekFilteredOut($dayOfWeek, $shiftBetweenDates) ||
                $this->isServiceProviderAlreadyAssignedToShift($shiftBetweenDates, $serviceProviderId)
            ) {
                continue;
            }

            $shiftsQualificationsValue = $this->shiftsQualificationsRepository
                ->findByShiftIdAndShiftQualificationId($shiftBetweenDates->id, $shiftQualificationId)?->value;

            if ($shiftsQualificationsValue === null || $shiftsQualificationsValue === 0) {
                continue;
            }

            if ($this->shiftWorkerService->getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
                    $shiftBetweenDates->id,
                    $shiftQualificationId
                ) >= $shiftsQualificationsValue) {
                continue;
            }

            // Nur für diese Schicht zuweisen (ohne Serienlogik erneut anzustoßen)
            $this->assignToShift(
                $shiftBetweenDates,
                $serviceProviderId,
                $shiftQualificationId,
                $craftAbbreviation,
                $shiftCountService,
                $changeService
            );
        }
    }


    /**
     * Service Provider aus Schicht entfernen (inkl. Serienlogik).
     */
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

        $shiftWorkerPivot = $this->shiftWorkerService->convertShiftServiceProviderToShiftWorker($shiftServiceProviderPivot);
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

    private function removeServiceProviderFromAllShiftsWithSameUuid(
        Shift $shift,
        ServiceProvider $serviceProvider,
        ShiftCountService $shiftCountService,
        ChangeService $changeService
    ): void {
        foreach ($this->shiftRepository->getShiftsByUuid($shift->shift_uuid) as $shiftByUuid) {
            if ($shiftByUuid->id === $shift->id) {
                continue;
            }

            $shiftServiceProviderPivotByUuid = $this->shiftRepository->getShiftWorkerPivotById(
                $shiftByUuid,
                ServiceProvider::class,
                $serviceProvider->id
            );

            if ($shiftServiceProviderPivotByUuid instanceof ShiftWorker) {
                $this->shiftWorkerService->removeFromShift(
                    $shiftServiceProviderPivotByUuid,
                    true,
                    null, // notificationService
                    null, // vacationConflictService
                    null, // availabilityConflictService
                    $changeService
                );
            }
        }
    }

    public function getShiftByUserPivotId(int $usersPivot): Shift
    {
        $shiftWorkerPivot = \Artwork\Modules\Shift\Models\ShiftWorker::find($usersPivot);
        if ($shiftWorkerPivot && $shiftWorkerPivot->employable_type === ServiceProvider::class) {
            if (!$shiftWorkerPivot->relationLoaded('shift')) {
                $shiftWorkerPivot->load('shift');
            }

            $shift = $shiftWorkerPivot->shift;
            if (!$shift) {
                throw new \RuntimeException("Shift for ShiftWorker pivot ID {$usersPivot} not found (shift_id: {$shiftWorkerPivot->shift_id})");
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
            throw new \RuntimeException("Shift for ShiftServiceProvider pivot ID {$usersPivot} not found (shift_id: {$shiftServiceProviderPivot->shift_id})");
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


    private function logManualRemovalActivity(Shift $shift, ShiftServiceProvider $shiftServiceProvider): void
    {
        if (! $shift->is_committed && ! $shift->in_workflow) {
            return;
        }

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event('removed')
            ->tap(function ($activity) use ($shift, $shiftServiceProvider): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => '{0} removed from shift as {1} for {2} ({3})',
                    'translation_key_placeholder_values' => [
                        $shiftServiceProvider->serviceProvider->name,
                        $shiftServiceProvider->shiftQualification->name,
                        $shift->craft->name,
                        $shiftServiceProvider->craft_abbreviation,
                    ],
                ]);
            })
            ->log('Service provider removed from shift');
    }

    private function logManualAssignmentActivity(Shift $shift, ShiftServiceProvider $shiftServiceProvider): void
    {
        if (! $shift->is_committed && ! $shift->in_workflow) {
            return;
        }

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event('assigned')
            ->tap(function ($activity) use ($shift, $shiftServiceProvider): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
                    'translation_key_placeholder_values' => [
                        $shiftServiceProvider->serviceProvider->name,
                        $shiftServiceProvider->shiftQualification->name,
                        $shift->craft->name,
                        $shiftServiceProvider->craft_abbreviation,
                    ],
                ]);
            })
            ->log('Service provider assigned to shift');
    }
}
