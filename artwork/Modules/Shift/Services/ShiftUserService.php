<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Events\ShiftAssigned;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;

class ShiftUserService
{
    public function __construct(
        private readonly ShiftRepository $shiftRepository,
        private readonly ShiftUserRepository $shiftUserRepository,
        private readonly ShiftFreelancerRepository $shiftFreelancerRepository,
        private readonly ShiftServiceProviderRepository $shiftServiceProviderRepository,
        private readonly ShiftsQualificationsRepository $shiftsQualificationsRepository,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        protected AuthManager $auth,
    ) {
    }

    /**
     * User einer Schicht zuweisen (inkl. Serienlogik).
     */
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
        ?array $seriesShiftData = null
    ): void {
        if ($this->isUserAlreadyAssignedToShift($shift, $userId)) {
            return;
        }

        $shiftUserPivot = $this->shiftUserRepository->createForShift(
            $shift->getAttribute('id'),
            $userId,
            $shiftQualificationId,
            $craftAbbreviation,
            $shift
        );


        /** @var User $user */
        $user = $shiftUserPivot->user;



        $this->shiftsQualificationsService->increaseValueOrCreateWithOne(
            $shift->getAttribute('id'),
            $shiftQualificationId
        );

        $shiftCountService->handleShiftUsersShiftCount($shift, $userId);
        $this->assignUserToProjectIfNecessary($shift, $user);

        if ($shift->is_committed) {
            $this->handleAssignedToShift(
                $shift,
                $user,
                $shiftUserPivot->shiftQualification,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }

        if ($this->shouldHandleSeriesShift($seriesShiftData)) {
            $this->handleSeriesShiftData(
                $shift,
                Carbon::parse($seriesShiftData['start'])->startOfDay(),
                Carbon::parse($seriesShiftData['end'])->endOfDay(),
                $seriesShiftData['dayOfWeek'],
                $userId,
                $shiftQualificationId,
                $craftAbbreviation,
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }


        $this->logCommittedShiftAssignmentChange(
            $shift,
            $user,
            'user_assigned_to_shift',
            $shiftUserPivot
        );

        $this->logManualAssignmentActivity($shift, $shiftUserPivot);

    }

    private function isUserAlreadyAssignedToShift(Shift $shift, int $userId): bool
    {
        return $shift->users()
            ->get(['users.id'])
            ->pluck('id')
            ->contains($userId);
    }

    private function shouldHandleSeriesShift(?array $seriesShiftData): bool
    {
        return $seriesShiftData !== null
            && isset($seriesShiftData['onlyThisDay'])
            && $seriesShiftData['onlyThisDay'] === false;
    }

    private function logManualAssignmentActivity(Shift $shift, ShiftUser $shiftUserPivot): void
    {
        if (! $shift->is_committed && ! $shift->in_workflow) {
            return;
        }

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event('assigned')
            ->tap(function ($activity) use ($shift, $shiftUserPivot): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
                    'translation_key_placeholder_values' => [
                        $shiftUserPivot->user->getFullNameAttribute(),
                        $shiftUserPivot->shiftQualification->name,
                        $shift->craft->name,
                        $shiftUserPivot->craft_abbreviation,
                    ],
                ]);
            })
            ->log('User assigned to shift');
    }

    private function handleAssignedToShift(
        Shift $shift,
        User $user,
        ShiftQualification $shiftQualification,
        NotificationService $notificationService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        if ($shift->event?->exists) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Employee was added to the shift as')
                    ->setTranslationKeyPlaceholderValues([
                        $user->getFullNameAttribute(),
                        $shift->craft->abbreviation,
                        $shift->event->eventName ?? '',
                        $shiftQualification->name,
                    ])
            );
        }

        $this->createAssignedToShiftNotification($shift, $user, $notificationService);

        if ($this->userHasVacationConflictWithShift($shift, $user)) {
            $this->createVacationConflictNotification($shift, $user, $notificationService);
        }

        $this->checkShortBreakAndCreateNotificationsIfNecessary($shift, $user, $notificationService);
        $this->checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary($shift, $user, $notificationService);

        $vacationConflictService->checkVacationConflictsShifts($shift, $notificationService, $user);
        $availabilityConflictService->checkAvailabilityConflictsShifts($shift, $notificationService, $user);

        broadcast(new ShiftAssigned($user, $shift));
    }

    private function userHasVacationConflictWithShift(Shift $shift, User $user): bool
    {
        return $user->vacations()
                ->where('date', '<=', $shift?->event_start_day ?? $shift->start_date)
                ->where('date', '>=', $shift?->event_end_day ?? $shift->end_date)
                ->count() > 0;
    }

    private function assignUserToProjectIfNecessary(Shift $shift, User $user): void
    {
        $project = $shift->event?->project;

        if ($project && ! $project->users->contains($user->id)) {
            $project->users()->attach($user->id);
        }
    }

    private function createAssignedToShiftNotification(
        Shift $shift,
        User $user,
        NotificationService $notificationService
    ): void {
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

    private function createVacationConflictNotification(
        Shift $shift,
        User $user,
        NotificationService $notificationService
    ): void {
        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CONFLICT);
        $notificationService->setButtons(['change_shift_conflict']);

        $usersWhichGotNotification = [];

        foreach ($user->crafts as $craft) {
            foreach ($craft->users as $craftUser) {
                if (in_array($craftUser->id, $usersWhichGotNotification, true)) {
                    continue;
                }

                $notificationTitle = __('notification.shift.conflict_shift_withName', [
                    'date'              => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                    'projectName'       => $shift?->event?->project?->name
                        ?? __('notification.shift.without_project'),
                    'craftAbbreviation' => $shift->craft->abbreviation,
                ], $craftUser->language);

                $notificationService->setTitle($notificationTitle);
                $notificationService->setBroadcastMessage([
                    'id'      => random_int(1, 1000000),
                    'type'    => 'success',
                    'message' => $notificationTitle,
                ]);
                $notificationService->setDescription([
                    1 => [
                        'type'  => 'string',
                        'title' => __(
                            'notification.keyWords.not_available',
                            ['username' => $user->getFullNameAttribute()]
                        ),
                        'href'  => null,
                    ],
                ]);

                $notificationService->setNotificationTo($craftUser);
                $notificationService->createNotification();
                $usersWhichGotNotification[] = $craftUser->id;
            }
        }

        $notificationService->clearNotificationData();
    }

    private function checkShortBreakAndCreateNotificationsIfNecessary(
        Shift $shift,
        User $user,
        NotificationService $notificationService
    ): void {
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
                'title' => __(
                    'notification.keyWords.concerns',
                    [],
                    $user->language
                ) . $user->getFullNameAttribute(),
                'href'  => null,
            ],
            2 => [
                'type'  => 'string',
                'title' => __(
                    'notification.keyWords.concerns_time_period',
                    [
                        'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y'),
                        'end'   => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),
                    ],
                    $user->language
                ),
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
                    'title' => __(
                        'notification.keyWords.concerns',
                        [],
                        $adminUser->language
                    ) . $user->getFullNameAttribute(),
                    'href'  => null,
                ],
                2 => [
                    'type'  => 'string',
                    'title' => __(
                        'notification.keyWords.concerns_time_period',
                        [
                            'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)
                                ->format('d.m.Y'),
                            'end'   => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)
                                ->format('d.m.Y'),
                        ],
                        $adminUser->language
                    ),
                    'href'  => null,
                ],
            ]);
            $notificationService->setNotificationTo($adminUser);
            $notificationService->createNotification();
        }
    }

    private function notifyShortBreakCraftUsers(
        $shiftBreakCheck,
        Shift $shift,
        User $user,
        NotificationService $notificationService
    ): void {
        $usersWhichGotNotification = [];

        foreach ($shift->craft->users as $craftUser) {
            if ($craftUser->id === $user->id) {
                continue;
            }

            if (in_array($craftUser->id, $usersWhichGotNotification, true)) {
                continue;
            }

            $notificationTitle = __('notification.shift.worker_short_break', [], $craftUser->language);

            $notificationService->setTitle($notificationTitle);
            $notificationService->setDescription([
                1 => [
                    'type'  => 'string',
                    'title' => __(
                        'notification.keyWords.concerns',
                        [],
                        $craftUser->language
                    ) . $user->getFullNameAttribute(),
                    'href'  => null,
                ],
                2 => [
                    'type'  => 'string',
                    'title' => __(
                        'notification.keyWords.concerns_time_period',
                        [
                            'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)
                                ->format('d.m.Y'),
                            'end'   => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)
                                ->format('d.m.Y'),
                        ],
                        $craftUser->language
                    ),
                    'href'  => null,
                ],
            ]);

            $notificationService->setNotificationTo($craftUser);
            $notificationService->createNotification();
            $usersWhichGotNotification[] = $craftUser->id;
        }
    }

    private function checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary(
        Shift $shift,
        User $user,
        NotificationService $notificationService
    ): void {
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
                'title' => __(
                    'notification.keyWords.concerns',
                    [],
                    $user->language
                ) . $user->getFullNameAttribute(),
                'href'  => null,
            ],
            2 => [
                'type'  => 'string',
                'title' => __(
                    'notification.keyWords.concerns_time_period',
                    [
                        'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)
                            ->format('d.m.Y'),
                        'end'   => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)
                            ->format('d.m.Y'),
                    ],
                    $user->language
                ),
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
                    'title' => __(
                        'notification.keyWords.concerns',
                        [],
                        $user->language
                    ) . $user->getFullNameAttribute(),
                    'href'  => null,
                ],
                2 => [
                    'type'  => 'string',
                    'title' => __(
                        'notification.keyWords.concerns_time_period',
                        [
                            'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)
                                ->format('d.m.Y'),
                            'end'   => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)
                                ->format('d.m.Y'),
                        ],
                        $user->language
                    ),
                    'href'  => null,
                ],
            ];

            $notificationService->setBroadcastMessage($broadcastMessage);
            $notificationService->setDescription($notificationDescription);
            $notificationService->setNotificationTo($adminUser);
            $notificationService->createNotification();
        }
    }

    private function notifyMoreThanTenShiftsCraftUsers(
        $shiftCheck,
        User $user,
        NotificationService $notificationService
    ): void {
        $usersWhichGotNotification = [];

        foreach ($user->crafts as $craft) {
            foreach ($craft->users as $craftUser) {
                if ($craftUser->id === $user->id) {
                    continue;
                }

                if (in_array($craftUser->id, $usersWhichGotNotification, true)) {
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
                        'title' => __(
                            'notification.keyWords.concerns',
                            [],
                            $craftUser->language
                        ) . $user->getFullNameAttribute(),
                        'href'  => null,
                    ],
                    2 => [
                        'type'  => 'string',
                        'title' => __(
                            'notification.keyWords.concerns_time_period',
                            [
                                'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)
                                    ->format('d.m.Y'),
                                'end'   => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)
                                    ->format('d.m.Y'),
                            ],
                            $craftUser->language
                        ),
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

    private function handleSeriesShiftData(
        Shift $shift,
        Carbon $start,
        Carbon $end,
        string $dayOfWeek,
        int $userId,
        int $shiftQualificationId,
        string $craftAbbreviation,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        /** @var Shift $shiftBetweenDates */
        foreach ($this->shiftRepository->getShiftsByUuidBetweenDates($shift->shift_uuid, $start, $end) as $shiftBetweenDates) {
            if (
                $this->isSameShift($shift, $shiftBetweenDates) ||
                $this->isDayOfWeekFilteredOut($dayOfWeek, $shiftBetweenDates) ||
                $this->isUserAlreadyAssignedToShift($shiftBetweenDates, $userId)
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
                $userId,
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
     * Entfernt einen User aus einer Schicht (inkl. Serienlogik).
     */
    public function removeFromShift(
        ShiftUser|int $usersPivot,
        bool $removeFromSingleShift,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $shiftUserPivot = ! $usersPivot instanceof ShiftUser
            ? $this->shiftUserRepository->getById($usersPivot)
            : $usersPivot;

        if (! $shiftUserPivot) {
            return;
        }

        /** @var Shift|null $shift */
        $shift = $shiftUserPivot->shift;
        if (! $shift) {
            return;
        }

        /** @var User|null $user */
        $user = $shiftUserPivot->user;
        if (! $user) {
            return;
        }

        $this->logManualRemovalActivity($shift, $shiftUserPivot);

        $this->logCommittedShiftAssignmentChange(
            $shift,
            $user,
            'user_removed_from_shift',
            $shiftUserPivot
        );

        $this->forceDelete($shiftUserPivot);

        if ($shift->is_committed) {
            $this->handleRemovedFromShift(
                $shift,
                $user,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }

        if (! $removeFromSingleShift) {
            $this->removeUserFromAllShiftsWithSameUuid(
                $shift,
                $user,
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }
    }

    private function logManualRemovalActivity(Shift $shift, ShiftUser $shiftUserPivot): void
    {
        if (! $shift->is_committed && ! $shift->in_workflow) {
            return;
        }

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event('removed')
            ->tap(function ($activity) use ($shift, $shiftUserPivot): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => '{0} removed from shift as {1} for {2} ({3})',
                    'translation_key_placeholder_values' => [
                        $shiftUserPivot->user->getFullNameAttribute(),
                        $shiftUserPivot->shiftQualification->name,
                        $shift->craft->name,
                        $shiftUserPivot->craft_abbreviation,
                    ],
                ]);
            })
            ->log('User removed from shift');
    }

    private function removeUserFromAllShiftsWithSameUuid(
        Shift $shift,
        User $user,
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

            $shiftUserPivotByUuid = $this->shiftRepository->getShiftUserPivotById($shiftByUuid, $user->id);

            if ($shiftUserPivotByUuid instanceof ShiftUser) {
                $this->removeFromShift(
                    $shiftUserPivotByUuid,
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
        $shiftUserPivot = ! $usersPivot instanceof ShiftUser
            ? $this->shiftUserRepository->getById($usersPivot)
            : $usersPivot;

        /** @var Shift $shiftUserPivot */
        return $shiftUserPivot->shift;
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
        User $user,
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
                    ->setTranslationKey('Employee was removed from shift')
                    ->setTranslationKeyPlaceholderValues([
                        $user->getFullNameAttribute(),
                        $shift->craft->abbreviation,
                        $shift->event?->eventName,
                    ])
            );

            $notificationService->setProjectId($shift?->event?->project?->id);
            $notificationService->setEventId($shift?->event?->id);
        }

        $notificationService->setShiftId($shift->id);

        $notificationTitle = __(
            'notification.shift.shift_staffing_deleted',
            [
                'projectName'       => $shift?->event?->project?->name ??
                    __('notification.shift.without_project'),
                'craftAbbreviation' => $shift->craft->abbreviation,
            ],
            $user->language
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
                'title' => __('notification.keyWords.concerns_shift', [], $user->language) .
                    Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href'  => null,
            ],
        ]);
        $notificationService->setNotificationTo($user);
        $notificationService->createNotification();
        $notificationService->clearNotificationData();

        $vacationConflictService->checkVacationConflictsShifts($shift, $notificationService, $user);
        $availabilityConflictService->checkAvailabilityConflictsShifts($shift, $notificationService, $user);
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
        if (! $shift->is_committed) {
            return;
        }

        $fieldChanges = [
            'assignment' => [
                'user_id'             => $user->id,
                'user_name'           => $user->full_name,
                'profile_picture_url' => $user->profile_photo_url,
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
                // Bei Zuweisung: vorher "free", nachher Arbeitszeit
                if ($changeType === 'user_assigned_to_shift') {
                    $fieldChanges['assignment']['before_label'] = 'free';
                    $fieldChanges['assignment']['after_label']  = $workingTimeLabel;
                }

                // Beim Entfernen: vorher Arbeitszeit, nachher "free"
                if ($changeType === 'user_removed_from_shift') {
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
            'affected_user_type'      => \Artwork\Modules\User\Models\User::class,
            'affected_user_id'        => $user->id,
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
    private function formatWorkingTimeLabel(Shift $shift, ?ShiftUser $pivot): ?string
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

}
