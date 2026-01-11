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
        if ($this->isServiceProviderAlreadyAssignedToShift($shift, $serviceProviderId)) {
            return;
        }

        $shiftServiceProviderPivot = $this->shiftServiceProviderRepository->createForShift(
            $shift->id,
            $serviceProviderId,
            $shiftQualificationId,
            $craftAbbreviation,
            $shift
        );

        $this->shiftsQualificationsService->increaseValueOrCreateWithOne(
            $shift->getAttribute('id'),
            $shiftQualificationId
        );

        /** @var ServiceProvider $serviceProvider */
        $serviceProvider = $shiftServiceProviderPivot->serviceProvider;

        // Manuelles Activitylog
        $this->logManualAssignmentActivity($shift, $shiftServiceProviderPivot);

        $shiftCountService->handleShiftServiceProvidersShiftCount($shift, $serviceProviderId);

        if ($shift->is_committed && $shift?->event?->exists) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Service provider was added to the shift as')
                    ->setTranslationKeyPlaceholderValues([
                        $shiftServiceProviderPivot->serviceProvider->getNameAttribute(),
                        $shift->craft->abbreviation,
                        $shift->event->eventName,
                        $shiftServiceProviderPivot->shiftQualification->name
                    ])
            );
        }

        $this->logCommittedShiftAssignmentChange(
            $shift,
            $serviceProvider,
            'service_provider_assigned_to_shift',
            $shiftServiceProviderPivot
        );

        if ($this->shiftWorkerService->shouldHandleSeriesShift($seriesShiftData)) {
            $this->handleSeriesShiftData(
                $shift,
                Carbon::parse($seriesShiftData['start'])->startOfDay(),
                Carbon::parse($seriesShiftData['end'])->endOfDay(),
                $seriesShiftData['dayOfWeek'],
                $serviceProviderId,
                $shiftQualificationId,
                $craftAbbreviation,
                $shiftCountService,
                $changeService
            );
        }
    }

    private function isServiceProviderAlreadyAssignedToShift(Shift $shift, int $serviceProviderId): bool
    {
        return $shift->serviceProvider()
            ->get(['service_providers.id'])
            ->pluck('id')
            ->contains($serviceProviderId);
    }

    private function shouldHandleSeriesShift(?array $seriesShiftData): bool
    {
        return $seriesShiftData !== null
            && isset($seriesShiftData['onlyThisDay'])
            && $seriesShiftData['onlyThisDay'] === false;
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
        $shiftServiceProviderPivot = ! $serviceProvidersPivot instanceof ShiftServiceProvider
            ? $this->shiftServiceProviderRepository->getById($serviceProvidersPivot)
            : $serviceProvidersPivot;

        if (! $shiftServiceProviderPivot instanceof ShiftServiceProvider) {
            return;
        }

        /** @var Shift|null $shift */
        $shift = $shiftServiceProviderPivot->shift;
        if (! $shift) {
            return;
        }

        /** @var ServiceProvider|null $serviceProvider */
        $serviceProvider = $shiftServiceProviderPivot->serviceProvider;
        if (! $serviceProvider) {
            return;
        }

        // Manuelles Activitylog: vor Löschen, damit Pivot-Daten verfügbar sind
        $this->logManualRemovalActivity($shift, $shiftServiceProviderPivot);

        $this->forceDelete($shiftServiceProviderPivot);
        $shiftCountService->handleShiftServiceProvidersShiftCount($shift, $serviceProvider->id);

        if ($shift->is_committed && $shift?->event?->exists) {
            $changeService->saveFromBuilder(
                $changeService
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

        $this->logCommittedShiftAssignmentChange(
            $shift,
            $serviceProvider,
            'service_provider_removed_from_shift',
            $shiftServiceProviderPivot
        );

        if (! $removeFromSingleShift) {
            $this->removeServiceProviderFromAllShiftsWithSameUuid(
                $shift,
                $serviceProvider,
                $shiftCountService,
                $changeService
            );
        }
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
                $this->removeFromShift(
                    $shiftServiceProviderPivotByUuid,
                    true,
                    $shiftCountService,
                    $changeService
                );
            }
        }
    }

    public function getShiftByUserPivotId(int $usersPivot): Shift
    {
        $shiftServiceProviderPivot = ! $usersPivot instanceof ShiftServiceProvider
            ? $this->shiftServiceProviderRepository->getById($usersPivot)
            : $usersPivot;

        /** @var Shift $shiftServiceProviderPivot */
        return $shiftServiceProviderPivot->shift;
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
        $shiftWorker = $pivot ? $this->convertShiftServiceProviderToShiftWorker($pivot) : null;
        $this->shiftWorkerService->logCommittedShiftAssignmentChange(
            $shift,
            $serviceProvider,
            $changeType,
            ServiceProvider::class,
            $shiftWorker
        );
    }

    private function convertShiftServiceProviderToShiftWorker(?ShiftServiceProvider $shiftServiceProvider): ?ShiftWorker
    {
        if (! $shiftServiceProvider) {
            return null;
        }

        return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
            ServiceProvider::class,
            $shiftServiceProvider->service_provider_id,
            $shiftServiceProvider->shift_id
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
