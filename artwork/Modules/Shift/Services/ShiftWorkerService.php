<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;

class ShiftWorkerService
{
    public function __construct(
        private readonly ShiftWorkerRepository $shiftWorkerRepository,
        protected AuthManager $auth
    ) {
    }

    protected function shouldHandleSeriesShift(?array $seriesShiftData): bool
    {
        return $seriesShiftData !== null
            && isset($seriesShiftData['onlyThisDay'])
            && $seriesShiftData['onlyThisDay'] === false;
    }

    protected function isSameShift(Shift $shift, Shift $otherShift): bool
    {
        return $otherShift->id === $shift->id;
    }

    protected function isDayOfWeekFilteredOut(string $dayOfWeek, Shift $shift): bool
    {
        if ($dayOfWeek === 'all') {
            return false;
        }

        return Carbon::parse($shift->event_start_day)->dayOfWeek !== (int) $dayOfWeek;
    }

    public function getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
        int $shiftId,
        int $shiftQualificationId
    ): int {
        return $this->shiftWorkerRepository->getCountForShiftIdAndShiftQualificationId(
            $shiftId,
            $shiftQualificationId
        );
    }

    protected function formatWorkingTimeLabel(Shift $shift, ?ShiftWorker $pivot): ?string
    {
        $startDate = $pivot?->start_date ?? $shift->start_date;
        $endDate = $pivot?->end_date ?? $shift->end_date;
        $startTime = $pivot?->start_time ?? $shift->start;
        $endTime = $pivot?->end_time ?? $shift->end;

        if (! $startDate || ! $endDate || ! $startTime || ! $endTime) {
            return null;
        }

        $startDateCarbon = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDateCarbon = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        $startTimeCarbon = $startTime instanceof Carbon ? $startTime : Carbon::parse($startTime);
        $endTimeCarbon = $endTime instanceof Carbon ? $endTime : Carbon::parse($endTime);

        if ($startDateCarbon->isSameDay($endDateCarbon)) {
            return sprintf(
                '%s %s - %s',
                $startDateCarbon->format('d.m.Y'),
                $startTimeCarbon->format('H:i'),
                $endTimeCarbon->format('H:i')
            );
        }

        return sprintf(
            '%s %s - %s %s',
            $startDateCarbon->format('d.m.Y'),
            $startTimeCarbon->format('H:i'),
            $endDateCarbon->format('d.m.Y'),
            $endTimeCarbon->format('H:i')
        );
    }

    protected function logCommittedShiftAssignmentChange(
        Shift $shift,
        Employable $employable,
        string $changeType,
        string $affectedUserType,
        ?ShiftWorker $pivot = null
    ): void {
        if (! $shift->is_committed) {
            return;
        }

        $fieldChanges = [
            'assignment' => [
                'user_id' => $employable->id,
                'user_name' => $employable->name ?? $employable->getFullNameAttribute() ?? '',
                'profile_picture_url' => $employable->profile_photo_url ?? null,
            ],
        ];

        if ($pivot) {
            $fieldChanges['assignment']['shift_qualification_id'] = $pivot->shift_qualification_id;
            $fieldChanges['assignment']['shift_qualification_name'] = optional($pivot->shiftQualification)->name;
            $fieldChanges['assignment']['craft_abbreviation'] = $pivot->craft_abbreviation;

            $fieldChanges['assignment']['start_date'] = optional($pivot->start_date)?->format('Y-m-d');
            $fieldChanges['assignment']['end_date'] = optional($pivot->end_date)?->format('Y-m-d');
            $fieldChanges['assignment']['start_time'] = $pivot->start_time
                ? Carbon::parse($pivot->start_time)->format('H:i')
                : null;
            $fieldChanges['assignment']['end_time'] = $pivot->end_time
                ? Carbon::parse($pivot->end_time)->format('H:i')
                : null;

            $workingTimeLabel = $this->formatWorkingTimeLabel($shift, $pivot);

            if ($workingTimeLabel) {
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

                if (in_array($changeType, $assignedTypes, true)) {
                    $fieldChanges['assignment']['before_label'] = 'free';
                    $fieldChanges['assignment']['after_label'] = $workingTimeLabel;
                }

                if (in_array($changeType, $removedTypes, true)) {
                    $fieldChanges['assignment']['before_label'] = $workingTimeLabel;
                    $fieldChanges['assignment']['after_label'] = 'free';
                }
            }
        }

        CommittedShiftChange::create([
            'craft_id' => $shift->craft_id,
            'shift_id' => $shift->getKey(),
            'subject_type' => Shift::class,
            'subject_id' => $shift->getKey(),
            'change_type' => $changeType,
            'field_changes' => $fieldChanges,
            'affected_user_type' => $affectedUserType,
            'affected_user_id' => $employable->id,
            'changed_by_user_id' => $this->auth->id(),
            'changed_at' => now(),
            'acknowledged_at' => null,
            'acknowledged_by_user_id' => null,
        ]);
    }

    protected function logManualActivity(
        Shift $shift,
        ShiftWorker $pivot,
        string $event,
        string $logMessage,
        callable $getNameCallback
    ): void {
        if (! $shift->is_committed && ! $shift->in_workflow) {
            return;
        }

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event($event)
            ->tap(function ($activity) use ($shift, $pivot, $getNameCallback): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => $event === 'assigned'
                        ? '{0} was assigned to shift as {1} for {2} ({3})'
                        : '{0} removed from shift as {1} for {2} ({3})',
                    'translation_key_placeholder_values' => [
                        $getNameCallback($pivot),
                        $pivot->shiftQualification->name,
                        $shift->craft->name,
                        $pivot->craft_abbreviation,
                    ],
                ]);
            })
            ->log($logMessage);
    }
}
