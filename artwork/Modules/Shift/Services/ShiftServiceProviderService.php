<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
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
        private ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private ShiftsQualificationsService $shiftsQualificationsService,
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

        if ($this->shouldHandleSeriesShift($seriesShiftData)) {
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
                $this->isSameShift($shift, $shiftBetweenDates) ||
                $this->isDayOfWeekFilteredOut($dayOfWeek, $shiftBetweenDates) ||
                $this->isServiceProviderAlreadyAssignedToShift($shiftBetweenDates, $serviceProviderId)
            ) {
                continue;
            }

            $shiftsQualificationsValue = $this->shiftsQualificationsRepository
                ->findByShiftIdAndShiftQualificationId($shiftBetweenDates->id, $shiftQualificationId)?->value;

            if ($shiftsQualificationsValue === null || $shiftsQualificationsValue === 0) {
                continue;
            }

            if ($this->getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
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

    private function isSameShift(Shift $shift, Shift $otherShift): bool
    {
        return $otherShift->id === $shift->id;
    }

    private function isDayOfWeekFilteredOut(string $dayOfWeek, Shift $shift): bool
    {
        if ($dayOfWeek === 'all') {
            return false;
        }

        return Carbon::parse($shift->event_start_day)->dayOfWeek !== (int) $dayOfWeek;
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

            $shiftServiceProviderPivotByUuid = $this->shiftRepository->getShiftServiceProviderPivotById(
                $shiftByUuid,
                $serviceProvider->id
            );

            if ($shiftServiceProviderPivotByUuid instanceof ShiftServiceProvider) {
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
        if (!$shift->is_committed) {
            return;
        }

        $fieldChanges = [
            'assignment' => [
                'user_id'             => $serviceProvider->id,
                'user_name'           => $serviceProvider->name,
                'profile_picture_url' => $serviceProvider->profile_photo_url,
            ],
        ];

        if ($pivot) {
            $fieldChanges['assignment']['shift_qualification_id']   = $pivot->shift_qualification_id;
            $fieldChanges['assignment']['shift_qualification_name'] = optional($pivot->shiftQualification)->name;
            $fieldChanges['assignment']['craft_abbreviation']       = $pivot->craft_abbreviation;

            $fieldChanges['assignment']['start_date'] = optional($pivot->start_date)?->format('Y-m-d');
            $fieldChanges['assignment']['end_date']   = optional($pivot->end_date)?->format('Y-m-d');
            $fieldChanges['assignment']['start_time'] = $pivot->start_time
                ? Carbon::parse($pivot->start_time)->format('H:i')
                : null;
            $fieldChanges['assignment']['end_time']   = $pivot->end_time
                ? Carbon::parse($pivot->end_time)->format('H:i')
                : null;

            // 💡 Arbeitszeit-Label auf Basis von Pivot/Shift bauen
            $workingTimeLabel = $this->formatWorkingTimeLabel($shift, $pivot);

            if ($workingTimeLabel) {
                // Unterstütze verschiedene Change-Type-Konventionen (user, freelancer, service_provider)
                $assignedTypes = [
                    'user_assigned_to_shift',
                    'freelancer_assigned_to_shift',
                    'service_provider_assigned_to_shift',
                ];

                $removedTypes = [
                    'user_removed_from_shift',
                    'freelancer_removed_from_shift',
                    'service_provider_removed_from_shift',
                ];

                // Bei Zuweisung: vorher "free", nachher Arbeitszeit
                if (in_array($changeType, $assignedTypes, true)) {
                    $fieldChanges['assignment']['before_label'] = 'free';
                    $fieldChanges['assignment']['after_label']  = $workingTimeLabel;
                }

                // Beim Entfernen: vorher Arbeitszeit, nachher "free"
                if (in_array($changeType, $removedTypes, true)) {
                    $fieldChanges['assignment']['before_label'] = $workingTimeLabel;
                    $fieldChanges['assignment']['after_label']  = 'free';
                }
            }
        }

        CommittedShiftChange::create([
            'craft_id'                => $shift->craft_id,
            'shift_id'                => $shift->getKey(),
            'subject_type'            => Shift::class,
            'subject_id'              => $shift->getKey(),
            'change_type'             => $changeType,
            'field_changes'           => $fieldChanges,
            'affected_user_type'      => \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class,
            'affected_user_id'        => $serviceProvider->id,
            'changed_by_user_id'      => $this->auth->id(),
            'changed_at'              => now(),
            'acknowledged_at'         => null,
            'acknowledged_by_user_id' => null,
        ]);
    }

    /**
     * Baut ein kompaktes Arbeitszeit-Label aus Pivot-/Schichtdaten,
     * z.B. "21.11.2025 10:00 - 18:00" oder mit Enddatum, falls abweichend.
     */
    private function formatWorkingTimeLabel(Shift $shift, ?ShiftServiceProvider $pivot): ?string
    {
        // Fallback auf Shift, falls im Pivot nichts/teilweise gesetzt ist
        $startDate = $pivot?->start_date ?? $shift->start_date;
        $endDate   = $pivot?->end_date ?? $shift->end_date;
        $startTime = $pivot?->start_time ?? $shift->start;
        $endTime   = $pivot?->end_time ?? $shift->end;

        if (! $startDate || ! $endDate || ! $startTime || ! $endTime) {
            return null;
        }

        $startDateCarbon = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDateCarbon   = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        $startTimeCarbon = $startTime instanceof Carbon ? $startTime : Carbon::parse($startTime);
        $endTimeCarbon   = $endTime instanceof Carbon ? $endTime : Carbon::parse($endTime);

        // Gleicher Tag → "21.11.2025 10:00 - 18:00"
        if ($startDateCarbon->isSameDay($endDateCarbon)) {
            return sprintf(
                '%s %s - %s',
                $startDateCarbon->format('d.m.Y'),
                $startTimeCarbon->format('H:i'),
                $endTimeCarbon->format('H:i')
            );
        }

        // Mehrtägig → "21.11.2025 10:00 - 22.11.2025 18:00"
        return sprintf(
            '%s %s - %s %s',
            $startDateCarbon->format('d.m.Y'),
            $startTimeCarbon->format('H:i'),
            $endDateCarbon->format('d.m.Y'),
            $endTimeCarbon->format('H:i')
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
