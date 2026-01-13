<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftWorkerRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;

class ShiftWorkerService
{
    public function __construct(
        private readonly ShiftWorkerRepository $shiftWorkerRepository,
        private readonly ShiftRepository $shiftRepository,
        private readonly ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftCountService $shiftCountService,
        protected AuthManager $auth
    ) {
    }

    public function shouldHandleSeriesShift(?array $seriesShiftData): bool
    {
        return $seriesShiftData !== null
            && isset($seriesShiftData['onlyThisDay'])
            && $seriesShiftData['onlyThisDay'] === false;
    }

    public function isSameShift(Shift $shift, Shift $otherShift): bool
    {
        return $otherShift->id === $shift->id;
    }

    public function isDayOfWeekFilteredOut(string $dayOfWeek, Shift $shift): bool
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

    public function logCommittedShiftAssignmentChange(
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
            ->tap(function ($activity) use ($shift, $pivot, $getNameCallback, $event): void {
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

    public function getChangeTypeForWorker(Employable $worker, bool $isRemoval = false): string
    {
        $prefix = match (true) {
            $worker instanceof User => 'user',
            $worker instanceof Freelancer => 'freelancer',
            $worker instanceof ServiceProvider => 'service_provider',
            default => 'worker',
        };

        return $isRemoval ? "{$prefix}_removed_from_shift" : "{$prefix}_assigned_to_shift";
    }

    public function isAlreadyAssigned(Shift $shift, Employable $worker): bool
    {
        $employableType = $worker::class;
        $employableId = $worker->id;

        return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
            $employableType,
            $employableId,
            $shift->id
        ) !== null;
    }

    public function supportsNotifications(Employable $worker): bool
    {
        return $worker instanceof User || $worker instanceof Freelancer;
    }

    public function getEmployableType(Employable $worker): string
    {
        return match (true) {
            $worker instanceof User => User::class,
            $worker instanceof Freelancer => Freelancer::class,
            $worker instanceof ServiceProvider => ServiceProvider::class,
            default => $worker::class,
        };
    }

    public function assignToShift(
        Shift $shift,
        Employable $worker,
        int $shiftQualificationId,
        string $craftAbbreviation,
        ?NotificationService $notificationService = null,
        ?VacationConflictService $vacationConflictService = null,
        ?AvailabilityConflictService $availabilityConflictService = null,
        ?ChangeService $changeService = null,
        ?array $seriesShiftData = null
    ): ShiftWorker {
        if ($this->isAlreadyAssigned($shift, $worker)) {
            return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
                $this->getEmployableType($worker),
                $worker->id,
                $shift->id
            );
        }

        $employableType = $this->getEmployableType($worker);

        $shiftWorkerPivot = $this->shiftWorkerRepository->createForShift(
            $shift->id,
            $employableType,
            $worker->id,
            $shiftQualificationId,
            $craftAbbreviation,
            $shift
        );

        $shiftWorkerPivot->setRelation('employable', $worker);
        $shiftWorkerPivot->load('shiftQualification');

        $this->shiftsQualificationsService->increaseValueOrCreateWithOne(
            $shift->id,
            $shiftQualificationId
        );

        match (true) {
            $worker instanceof User => $this->shiftCountService->handleShiftUsersShiftCount($shift, $worker->id),
            $worker instanceof Freelancer => $this->shiftCountService->handleShiftFreelancersShiftCount($shift, $worker->id),
            $worker instanceof ServiceProvider => $this->shiftCountService->handleShiftServiceProvidersShiftCount($shift, $worker->id),
            default => throw new \InvalidArgumentException("Unbekannter Worker-Typ: {$employableType}"),
        };

        if ($worker instanceof User) {
            $this->assignUserToProjectIfNecessary($shift, $worker);
        }

        if ($shift->is_committed && $this->supportsNotifications($worker)) {
            $this->handleAssignedToShift(
                $shift,
                $worker,
                $shiftWorkerPivot->shiftQualification,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        } elseif ($shift->is_committed && $worker instanceof ServiceProvider && $shift->event?->exists) {
            // ServiceProvider-spezifische Change-Logik
            if ($changeService) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setType('shift')
                        ->setModelClass(Shift::class)
                        ->setModelId($shift->id)
                        ->setShift($shift)
                        ->setTranslationKey('Service provider was added to the shift as')
                        ->setTranslationKeyPlaceholderValues([
                            $worker->getNameAttribute(),
                            $shift->craft->abbreviation,
                            $shift->event->eventName,
                            $shiftWorkerPivot->shiftQualification->name
                        ])
                );
            }
        }

        $this->logCommittedShiftAssignmentChange(
            $shift,
            $worker,
            $this->getChangeTypeForWorker($worker, false),
            $employableType,
            $shiftWorkerPivot
        );

        $this->logManualAssignmentActivity($shift, $shiftWorkerPivot, $worker);

        if ($this->shouldHandleSeriesShift($seriesShiftData)) {
            $this->handleSeriesShiftData(
                $shift,
                Carbon::parse($seriesShiftData['start'])->startOfDay(),
                Carbon::parse($seriesShiftData['end'])->endOfDay(),
                $seriesShiftData['dayOfWeek'],
                $worker,
                $shiftQualificationId,
                $craftAbbreviation,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }

        return $shiftWorkerPivot;
    }

    private function assignUserToProjectIfNecessary(Shift $shift, User $user): void
    {
        $project = $shift->event?->project;

        if ($project && ! $project->users->contains($user->id)) {
            $project->users()->attach($user->id);
        }
    }

    private function handleAssignedToShift(
        Shift $shift,
        Employable $worker,
        ShiftQualification $shiftQualification,
        ?NotificationService $notificationService,
        ?VacationConflictService $vacationConflictService,
        ?AvailabilityConflictService $availabilityConflictService,
        ?ChangeService $changeService
    ): void {
        if ($shift->event?->exists && $changeService) {
            $translationKey = match (true) {
                $worker instanceof User => 'Employee was added to the shift as',
                $worker instanceof Freelancer => 'Freelancer was added to the shift as',
                default => 'Worker was added to the shift as',
            };

            $name = match (true) {
                $worker instanceof User => $worker->getFullNameAttribute(),
                $worker instanceof Freelancer => $worker->getNameAttribute(),
                default => $worker->name ?? '',
            };

            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey($translationKey)
                    ->setTranslationKeyPlaceholderValues([
                        $name,
                        $shift->craft->abbreviation,
                        $shift->event->eventName ?? '',
                        $shiftQualification->name,
                    ])
            );
        }

        if ($worker instanceof User && $notificationService) {
            $this->createAssignedToShiftNotification($shift, $worker, $notificationService);
            $this->checkShortBreakAndCreateNotificationsIfNecessary($shift, $worker, $notificationService);
            $this->checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary($shift, $worker, $notificationService);
        }

        if ($vacationConflictService && $notificationService) {
            $vacationConflictService->checkVacationConflictsShifts($shift, $notificationService, $worker);
        }

        if ($availabilityConflictService && $notificationService) {
            $availabilityConflictService->checkAvailabilityConflictsShifts($shift, $notificationService, $worker);
        }

        broadcast(new ShiftAssigned($worker, $shift));
    }

    private function logManualAssignmentActivity(Shift $shift, ShiftWorker $pivot, Employable $worker): void
    {
        $getNameCallback = match (true) {
            $worker instanceof User => fn($p) => $p->employable->getFullNameAttribute(),
            $worker instanceof Freelancer => fn($p) => $p->employable->getNameAttribute(),
            $worker instanceof ServiceProvider => fn($p) => $p->employable->getNameAttribute(),
            default => fn($p) => $p->employable->name ?? '',
        };

        $this->logManualActivity($shift, $pivot, 'assigned', 'Worker assigned to shift', $getNameCallback);
    }

    public function convertShiftUserToShiftWorker(ShiftUser $shiftUser): ?ShiftWorker
    {
        return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
            User::class,
            $shiftUser->user_id,
            $shiftUser->shift_id
        );
    }

    public function convertShiftFreelancerToShiftWorker(ShiftFreelancer $shiftFreelancer): ?ShiftWorker
    {
        return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
            Freelancer::class,
            $shiftFreelancer->freelancer_id,
            $shiftFreelancer->shift_id
        );
    }

    public function convertShiftServiceProviderToShiftWorker(ShiftServiceProvider $shiftServiceProvider): ?ShiftWorker
    {
        return $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
            ServiceProvider::class,
            $shiftServiceProvider->service_provider_id,
            $shiftServiceProvider->shift_id
        );
    }

    public function removeFromShift(
        ShiftWorker $pivot,
        bool $removeFromSingleShift,
        ?NotificationService $notificationService = null,
        ?VacationConflictService $vacationConflictService = null,
        ?AvailabilityConflictService $availabilityConflictService = null,
        ?ChangeService $changeService = null
    ): void {
        $shift = $pivot->shift;
        if (!$shift) {
            return;
        }

        $worker = $pivot->employable;
        if (!$worker) {
            return;
        }

        $employableType = $this->getEmployableType($worker);

        $this->logManualRemovalActivity($shift, $pivot, $worker);

        $this->logCommittedShiftAssignmentChange(
            $shift,
            $worker,
            $this->getChangeTypeForWorker($worker, true),
            $employableType,
            $pivot
        );

        $pivot->delete();

        match (true) {
            $worker instanceof User => $this->shiftCountService->handleShiftUsersShiftCount($shift, $worker->id),
            $worker instanceof Freelancer => $this->shiftCountService->handleShiftFreelancersShiftCount($shift, $worker->id),
            $worker instanceof ServiceProvider => $this->shiftCountService->handleShiftServiceProvidersShiftCount($shift, $worker->id),
            default => throw new \InvalidArgumentException("Unbekannter Worker-Typ: {$employableType}"),
        };

        if ($shift->is_committed && $this->supportsNotifications($worker)) {
            $this->handleRemovedFromShift(
                $shift,
                $worker,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }

        if (!$removeFromSingleShift) {
            $this->removeWorkerFromAllShiftsWithSameUuid(
                $shift,
                $worker,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }
    }

    private function logManualRemovalActivity(Shift $shift, ShiftWorker $pivot, Employable $worker): void
    {
        $getNameCallback = match (true) {
            $worker instanceof User => fn($p) => $p->employable->getFullNameAttribute(),
            $worker instanceof Freelancer => fn($p) => $p->employable->getNameAttribute(),
            $worker instanceof ServiceProvider => fn($p) => $p->employable->getNameAttribute(),
            default => fn($p) => $p->employable->name ?? '',
        };

        $this->logManualActivity($shift, $pivot, 'removed', 'Worker removed from shift', $getNameCallback);
    }

    private function handleRemovedFromShift(
        Shift $shift,
        Employable $worker,
        ?NotificationService $notificationService,
        ?VacationConflictService $vacationConflictService,
        ?AvailabilityConflictService $availabilityConflictService,
        ?ChangeService $changeService
    ): void {
        if ($shift->event?->exists && $changeService) {
            $translationKey = match (true) {
                $worker instanceof User => 'Employee was removed from the shift',
                $worker instanceof Freelancer => 'Freelancer was removed from the shift',
                default => 'Worker was removed from the shift',
            };

            $name = match (true) {
                $worker instanceof User => $worker->getFullNameAttribute(),
                $worker instanceof Freelancer => $worker->getNameAttribute(),
                default => $worker->name ?? '',
            };

            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey($translationKey)
                    ->setTranslationKeyPlaceholderValues([
                        $name,
                        $shift->craft->abbreviation,
                        $shift->event->eventName ?? '',
                    ])
            );
        }

        if ($worker instanceof User && $notificationService) {
            if ($shift->event?->exists) {
                $notificationService->setProjectId($shift->event?->project?->id);
                $notificationService->setEventId($shift->event?->id);
            }

            $notificationService->setShiftId($shift->id);

            $notificationTitle = __(
                'notification.shift.shift_staffing_deleted',
                [
                    'projectName'       => $shift->event?->project?->name ??
                        __('notification.shift.without_project'),
                    'craftAbbreviation' => $shift->craft->abbreviation,
                ],
                $worker->language
            );

            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('red');
            $notificationService->setPriority(2);
            $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);
            $notificationService->setBroadcastMessage([
                'id'      => random_int(1, 1000000),
                'type'    => 'success',
                'message' => $notificationTitle,
            ]);
            $notificationService->setDescription([
                1 => [
                    'type'  => 'string',
                    'title' => __('notification.keyWords.your_shift') .
                        Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                        Carbon::parse($shift->end)->format('d.m.Y H:i'),
                    'href'  => null,
                ],
            ]);
            $notificationService->setNotificationTo($worker);
            $notificationService->createNotification();
            $notificationService->clearNotificationData();
        }
    }

    private function removeWorkerFromAllShiftsWithSameUuid(
        Shift $shift,
        Employable $worker,
        ?NotificationService $notificationService,
        ?VacationConflictService $vacationConflictService,
        ?AvailabilityConflictService $availabilityConflictService,
        ?ChangeService $changeService
    ): void {
        $employableType = $this->getEmployableType($worker);

        foreach ($this->shiftRepository->getShiftsByUuid($shift->shift_uuid) as $shiftByUuid) {
            if ($shiftByUuid->id === $shift->id) {
                continue;
            }

            $pivot = $this->shiftWorkerRepository->findByEmployableIdAndShiftId(
                $employableType,
                $worker->id,
                $shiftByUuid->id
            );

            if ($pivot) {
                $this->removeFromShift(
                    $pivot,
                    true, // removeFromSingleShift = true, um Rekursion zu vermeiden
                    $notificationService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
            }
        }
    }

    public function handleSeriesShiftData(
        Shift $shift,
        Carbon $start,
        Carbon $end,
        string $dayOfWeek,
        Employable $worker,
        int $shiftQualificationId,
        string $craftAbbreviation,
        ?NotificationService $notificationService = null,
        ?VacationConflictService $vacationConflictService = null,
        ?AvailabilityConflictService $availabilityConflictService = null,
        ?ChangeService $changeService = null
    ): void {
        $employableType = $this->getEmployableType($worker);

        foreach ($this->shiftRepository->getShiftsByUuidBetweenDates($shift->shift_uuid, $start, $end) as $shiftBetweenDates) {
            if (
                $this->isSameShift($shift, $shiftBetweenDates) ||
                $this->isDayOfWeekFilteredOut($dayOfWeek, $shiftBetweenDates) ||
                $this->isAlreadyAssigned($shiftBetweenDates, $worker)
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

            $this->assignToShift(
                $shiftBetweenDates,
                $worker,
                $shiftQualificationId,
                $craftAbbreviation,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService,
                null // seriesShiftData = null
            );
        }
    }

    // User-spezifische Notification-Methoden
    private function createAssignedToShiftNotification(Shift $shift, User $user, NotificationService $notificationService): void
    {
        if ($shift->event?->exists) {
            $notificationService->setProjectId($shift->event?->project->id);
            $notificationService->setEventId($shift->event->id);
        }

        $notificationService->setShiftId($shift->id);

        $notificationTitle = __('notification.shift.new_shift_add', [
            'craftName'         => $shift->craft->name,
            'craftAbbreviation' => $shift->craft->abbreviation,
        ], $user->language);

        $notificationService->setTitle($notificationTitle);
        $notificationService->setIcon('green');
        $notificationService->setPriority(3);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);
        $notificationService->setBroadcastMessage([
            'id'      => random_int(1, 1000000),
            'type'    => 'success',
            'message' => $notificationTitle,
        ]);
        $notificationService->setDescription([
            1 => [
                'type'  => 'string',
                'title' => __('notification.keyWords.your_shift') .
                    Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href'  => null,
            ],
        ]);
        $notificationService->setNotificationTo($user);
        $notificationService->createNotification();
        $notificationService->clearNotificationData();
    }


    private function checkShortBreakAndCreateNotificationsIfNecessary(Shift $shift, User $user, NotificationService $notificationService): void
    {
        $shiftBreakCheck = $notificationService->checkIfShortBreakBetweenTwoShifts($user, $shift);

        if (! $shiftBreakCheck->shortBreak) {
            return;
        }

        $this->notifyShortBreakUser($shiftBreakCheck, $user, $notificationService);
        $this->notifyShortBreakAdmins($shiftBreakCheck, $user, $notificationService);
        $this->notifyShortBreakCraftUsers($shiftBreakCheck, $shift, $user, $notificationService);

        $notificationService->clearNotificationData();
    }

    private function notifyShortBreakUser($shiftBreakCheck, User $user, NotificationService $notificationService): void
    {
        $notificationTitle = __('notification.shift.your_short_break', [], $user->language);

        $notificationService->setTitle($notificationTitle);
        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
        $notificationService->setBroadcastMessage([
            'id'      => random_int(1, 1000000),
            'type'    => 'error',
            'message' => $notificationTitle,
        ]);
        $notificationService->setDescription([
            1 => [
                'type'  => 'string',
                'title' => __('notification.keyWords.concerns', [], $user->language) . $user->getFullNameAttribute(),
                'href'  => null,
            ],
            2 => [
                'type'  => 'string',
                'title' => __('notification.keyWords.concerns_time_period', [
                    'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y'),
                    'end'   => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),
                ], $user->language),
                'href'  => null,
            ],
        ]);
        $notificationService->setNotificationTo($user);
        $notificationService->createNotification();
    }

    private function notifyShortBreakAdmins($shiftBreakCheck, User $user, NotificationService $notificationService): void
    {
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
        $notificationService->setButtons(['see_shift', 'delete_shift_notification']);

        foreach (User::role(RoleEnum::ARTWORK_ADMIN->value)->get() as $adminUser) {
            $notificationTitle = __('notification.shift.worker_short_break', [], $adminUser->language);

            $notificationService->setTitle($notificationTitle);
            $notificationService->setDescription([
                1 => [
                    'type'  => 'string',
                    'title' => __('notification.keyWords.concerns', [], $adminUser->language) . $user->getFullNameAttribute(),
                    'href'  => null,
                ],
                2 => [
                    'type'  => 'string',
                    'title' => __('notification.keyWords.concerns_time_period', [
                        'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y'),
                        'end'   => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),
                    ], $adminUser->language),
                    'href'  => null,
                ],
            ]);
            $notificationService->setNotificationTo($adminUser);
            $notificationService->createNotification();
        }
    }

    private function notifyShortBreakCraftUsers($shiftBreakCheck, Shift $shift, User $user, NotificationService $notificationService): void
    {
        $usersWhichGotNotification = [];

        foreach ($shift->craft->users as $craftUser) {
            if ($craftUser->id === $user->id || in_array($craftUser->id, $usersWhichGotNotification, true)) {
                continue;
            }

            $notificationTitle = __('notification.shift.worker_short_break', [], $craftUser->language);

            $notificationService->setTitle($notificationTitle);
            $notificationService->setDescription([
                1 => [
                    'type'  => 'string',
                    'title' => __('notification.keyWords.concerns', [], $craftUser->language) . $user->getFullNameAttribute(),
                    'href'  => null,
                ],
                2 => [
                    'type'  => 'string',
                    'title' => __('notification.keyWords.concerns_time_period', [
                        'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y'),
                        'end'   => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),
                    ], $craftUser->language),
                    'href'  => null,
                ],
            ]);

            $notificationService->setNotificationTo($craftUser);
            $notificationService->createNotification();
            $usersWhichGotNotification[] = $craftUser->id;
        }
    }

    private function checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary(Shift $shift, User $user, NotificationService $notificationService): void
    {
        $shiftCheck = $notificationService->checkIfUserInMoreThanTenShifts($user, $shift);

        if (! $shiftCheck->moreThanTenShifts) {
            $notificationService->clearNotificationData();
            return;
        }

        $this->notifyMoreThanTenShiftsUser($shiftCheck, $user, $notificationService);
        $this->notifyMoreThanTenShiftsAdmins($shiftCheck, $user, $notificationService);
        $this->notifyMoreThanTenShiftsCraftUsers($shiftCheck, $user, $notificationService);

        $notificationService->clearNotificationData();
    }

    private function notifyMoreThanTenShiftsUser($shiftCheck, User $user, NotificationService $notificationService): void
    {
        $notificationTitle = __('notification.shift.more_than_ten_days', [], $user->language);

        $notificationService->setTitle($notificationTitle);
        $notificationService->setIcon('red');
        $notificationService->setPriority(2);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
        $notificationService->setBroadcastMessage([
            'id'      => random_int(1, 1000000),
            'type'    => 'error',
            'message' => $notificationTitle,
        ]);
        $notificationService->setDescription([
            1 => [
                'type'  => 'string',
                'title' => __('notification.keyWords.concerns', [], $user->language) . $user->getFullNameAttribute(),
                'href'  => null,
            ],
            2 => [
                'type'  => 'string',
                'title' => __('notification.keyWords.concerns_time_period', [
                    'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)->format('d.m.Y'),
                    'end'   => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)->format('d.m.Y'),
                ], $user->language),
                'href'  => null,
            ],
        ]);

        $notificationService->setNotificationTo($user);
        $notificationService->createNotification();
    }

    private function notifyMoreThanTenShiftsAdmins($shiftCheck, User $user, NotificationService $notificationService): void
    {
        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
        $notificationService->setButtons(['see_shift', 'delete_shift_notification']);

        foreach (User::role(RoleEnum::ARTWORK_ADMIN->value)->get() as $adminUser) {
            $notificationTitle = __('notification.shift.worker_more_than_ten_days', [], $adminUser->language);

            $broadcastMessage = [
                'id'      => random_int(1, 1000000),
                'type'    => 'error',
                'message' => $notificationTitle,
            ];
            $notificationDescription = [
                1 => [
                    'type'  => 'string',
                    'title' => __('notification.keyWords.concerns', [], $user->language) . $user->getFullNameAttribute(),
                    'href'  => null,
                ],
                2 => [
                    'type'  => 'string',
                    'title' => __('notification.keyWords.concerns_time_period', [
                        'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)->format('d.m.Y'),
                        'end'   => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)->format('d.m.Y'),
                    ], $user->language),
                    'href'  => null,
                ],
            ];

            $notificationService->setBroadcastMessage($broadcastMessage);
            $notificationService->setDescription($notificationDescription);
            $notificationService->setNotificationTo($adminUser);
            $notificationService->createNotification();
        }
    }

    private function notifyMoreThanTenShiftsCraftUsers($shiftCheck, User $user, NotificationService $notificationService): void
    {
        $usersWhichGotNotification = [];

        foreach ($user->crafts as $craft) {
            foreach ($craft->users as $craftUser) {
                if ($craftUser->id === $user->id || in_array($craftUser->id, $usersWhichGotNotification, true)) {
                    continue;
                }

                $notificationTitle = __('notification.shift.worker_more_than_ten_days', [], $craftUser->language);

                $broadcastMessage = [
                    'id'      => random_int(1, 1000000),
                    'type'    => 'error',
                    'message' => $notificationTitle,
                ];
                $notificationDescription = [
                    1 => [
                        'type'  => 'string',
                        'title' => __('notification.keyWords.concerns', [], $craftUser->language) . $user->getFullNameAttribute(),
                        'href'  => null,
                    ],
                    2 => [
                        'type'  => 'string',
                        'title' => __('notification.keyWords.concerns_time_period', [
                            'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)->format('d.m.Y'),
                            'end'   => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)->format('d.m.Y'),
                        ], $craftUser->language),
                        'href'  => null,
                    ],
                ];

                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setDescription($notificationDescription);
                $notificationService->setNotificationTo($craftUser);
                $notificationService->createNotification();

                $usersWhichGotNotification[] = $craftUser->id;
            }
        }
    }
}
