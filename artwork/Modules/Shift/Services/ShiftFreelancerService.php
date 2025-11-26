<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
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
        private ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private ShiftsQualificationsService $shiftsQualificationsService,
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
        if ($this->isFreelancerAlreadyAssignedToShift($shift, $freelancerId)) {
            return;
        }

        $shiftFreelancerPivot = $this->shiftFreelancerRepository->createForShift(
            $shift->id,
            $freelancerId,
            $shiftQualificationId,
            $craftAbbreviation,
            $shift
        );

        /** @var Freelancer $freelancer */
        $freelancer = $shiftFreelancerPivot->freelancer;

        // Manuelles Activitylog
        $this->logManualAssignmentActivity($shift, $shiftFreelancerPivot);

        $this->shiftsQualificationsService->increaseValueOrCreateWithOne(
            $shift->getAttribute('id'),
            $shiftQualificationId
        );

        $shiftCountService->handleShiftFreelancersShiftCount($shift, $freelancerId);

        if ($shift->is_committed) {
            $this->handleAssignedToShift(
                $shift,
                $freelancer,
                $shiftFreelancerPivot->shiftQualification,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }

        $this->logCommittedShiftAssignmentChange(
            $shift,
            $freelancer,
            'freelancer_assigned_to_shift',
            $shiftFreelancerPivot
        );

        if ($this->shouldHandleSeriesShift($seriesShiftData)) {
            $this->handleSeriesShiftData(
                $shift,
                Carbon::parse($seriesShiftData['start'])->startOfDay(),
                Carbon::parse($seriesShiftData['end'])->endOfDay(),
                $seriesShiftData['dayOfWeek'],
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

    private function isFreelancerAlreadyAssignedToShift(Shift $shift, int $freelancerId): bool
    {
        return $shift->freelancer()
            ->get(['freelancers.id'])
            ->pluck('id')
            ->contains($freelancerId);
    }

    private function shouldHandleSeriesShift(?array $seriesShiftData): bool
    {
        return $seriesShiftData !== null
            && isset($seriesShiftData['onlyThisDay'])
            && $seriesShiftData['onlyThisDay'] === false;
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
                $this->isSameShift($shift, $shiftBetweenDates) ||
                $this->isDayOfWeekFilteredOut($dayOfWeek, $shiftBetweenDates) ||
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
                $this->getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
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
        $shiftFreelancerPivot = ! $freelancersPivot instanceof ShiftFreelancer
            ? $this->shiftFreelancerRepository->getById($freelancersPivot)
            : $freelancersPivot;

        if (! $shiftFreelancerPivot instanceof ShiftFreelancer) {
            return;
        }

        /** @var Shift|null $shift */
        $shift = $shiftFreelancerPivot->shift;
        if (! $shift) {
            return;
        }

        /** @var Freelancer|null $freelancer */
        $freelancer = $shiftFreelancerPivot->freelancer;
        if (! $freelancer) {
            return;
        }

        // Manuelles Activitylog: vor Löschen, damit Pivot-Daten verfügbar sind
        $this->logManualRemovalActivity($shift, $shiftFreelancerPivot);

        $this->forceDelete($shiftFreelancerPivot);
        $shiftCountService->handleShiftFreelancersShiftCount($shift, $freelancer->id);

        if ($shift->is_committed) {
            $this->handleRemovedFromShift(
                $shift,
                $freelancer,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }

        $this->logCommittedShiftAssignmentChange(
            $shift,
            $freelancer,
            'freelancer_removed_from_shift',
            $shiftFreelancerPivot
        );

        if (! $removeFromSingleShift) {
            $this->removeFreelancerFromAllShiftsWithSameUuid(
                $shift,
                $freelancer,
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }
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

            $shiftFreelancerPivotByUuid = $this->shiftRepository->getShiftFreelancerPivotById(
                $shiftByUuid,
                $freelancer->id
            );

            if ($shiftFreelancerPivotByUuid instanceof ShiftFreelancer) {
                $this->removeFromShift(
                    $shiftFreelancerPivotByUuid,
                    true,
                    $notificationService,
                    $shiftCountService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
            }
        }
    }

    public function getShiftByUserPivotId(int $usersPivot): Shift
    {
        $shiftFreelancerPivot = ! $usersPivot instanceof ShiftFreelancer
            ? $this->shiftFreelancerRepository->getById($usersPivot)
            : $usersPivot;

        /** @var Shift $shiftFreelancerPivot */
        return $shiftFreelancerPivot->shift;
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
        if (! $shift->is_committed) {
            return;
        }

        $fieldChanges = [
            'assignment' => [
                'user_id'             => $freelancer->id,
                'user_name'           => $freelancer->name,
                'profile_picture_url' => $freelancer->profile_photo_url,
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
            'affected_user_type'      => \Artwork\Modules\Freelancer\Models\Freelancer::class,
            'affected_user_id'        => $freelancer->id,
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
    private function formatWorkingTimeLabel(Shift $shift, ?ShiftFreelancer $pivot): ?string
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
