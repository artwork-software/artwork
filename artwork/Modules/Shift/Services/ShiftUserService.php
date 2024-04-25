<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Repositories\ShiftFreelancerRepository;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Repositories\ShiftServiceProviderRepository;
use Artwork\Modules\Shift\Repositories\ShiftsQualificationsRepository;
use Artwork\Modules\Shift\Repositories\ShiftUserRepository;
use Artwork\Modules\ShiftQualification\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;

readonly class ShiftUserService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftUserRepository $shiftUserRepository,
        private ShiftFreelancerRepository $shiftFreelancerRepository,
        private ShiftServiceProviderRepository $shiftServiceProviderRepository,
        private ShiftsQualificationsRepository $shiftsQualificationsRepository
    ) {
    }

    public function assignToShift(
        Shift $shift,
        int $userId,
        int $shiftQualificationId,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService,
        array|null $seriesShiftData = null
    ): void {
        $shiftUserPivot = $this->shiftUserRepository->createForShift(
            $shift->id,
            $userId,
            $shiftQualificationId
        );

        $shiftCountService->handleShiftUsersShiftCount($shift, $userId);

        /** @var User $user */
        $user = $shiftUserPivot->user;
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

        if (
            $seriesShiftData !== null &&
            isset($seriesShiftData['onlyThisDay']) &&
            $seriesShiftData['onlyThisDay'] === false
        ) {
            $this->handleSeriesShiftData(
                $shift,
                Carbon::parse($seriesShiftData['start'])->startOfDay(),
                Carbon::parse($seriesShiftData['end'])->endOfDay(),
                $seriesShiftData['dayOfWeek'],
                $userId,
                $shiftQualificationId,
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }
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
                    $shift->event->eventName,
                    $shiftQualification->name
                ])
        );
        $this->createAssignedToShiftNotification($shift, $user, $notificationService);
        if (
            $user->vacations()
                ->where('date', '<=', $shift->event_start_day)
                ->where('date', '>=', $shift->event_end_day)
                ->count() > 0
        ) {
            $this->createVacationConflictNotification($shift, $user, $notificationService);
        }
        $this->checkShortBreakAndCreateNotificationsIfNecessary($shift, $user, $notificationService);
        $this->checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary($shift, $user, $notificationService);

        $vacationConflictService->checkVacationConflictsShifts($shift, $notificationService, $user);
        $availabilityConflictService->checkAvailabilityConflictsShifts($shift, $notificationService, $user);
    }

    private function assignUserToProjectIfNecessary(Shift $shift, User $user): void
    {
        $project = $shift->event->project;
        if (!$project->users->contains($user->id)) {
            $project->users()->attach($user->id);
        }
    }

    private function createAssignedToShiftNotification(
        Shift $shift,
        User $user,
        NotificationService $notificationService
    ): void {
        $notificationService->setProjectId($shift->event->project->id);
        $notificationService->setEventId($shift->event->id);
        $notificationService->setShiftId($shift->id);
        $notificationTitle = __('notification.shift.new_shift_add', [
            'projectName' => $shift->event->project->name,
            'craftAbbreviation' => $shift->craft->abbreviation
        ], $user->language);
        $notificationService->setTitle($notificationTitle);
        $notificationService->setIcon('green');
        $notificationService->setPriority(3);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);
        $notificationService->setBroadcastMessage([
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ]);
        $notificationService->setDescription([
            1 => [
                'type' => 'string',
                'title' => __('notification.keyWords.your_shift') .
                    Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
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
        $notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CONFLICT);

        $notificationService->setButtons(['change_shift_conflict']);
        $usersWhichGotNotification = [];
        foreach ($user->crafts as $craft) {
            foreach ($craft->users as $craftUser) {
                if (in_array($craftUser->id, $usersWhichGotNotification)) {
                    continue;
                }
                $notificationTitle = __('notification.shift.conflict_shift_withName', [
                    'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                    'projectName' => $shift->event->project->name,
                    'craftAbbreviation' => $shift->craft->abbreviation
                ], $craftUser->language);
                $notificationService->setTitle($notificationTitle);
                $notificationService->setBroadcastMessage([
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ]);
                $notificationService->setDescription([
                    1 => [
                        'type' => 'string',
                        'title' => __(
                            'notification.keyWords.not_available',
                            ['username' => $user->getFullNameAttribute()]
                        ),
                        'href' => null
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

        if ($shiftBreakCheck->shortBreak) {
            $notificationTitle = __('notification.shift.your_short_break', [], $user->language);
            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('blue');
            $notificationService->setPriority(1);
            $notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $notificationService->setBroadcastMessage([
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ]);
            $notificationService->setDescription([
                1 => [
                    'type' => 'string',
                    'title' => __(
                        'notification.keyWords.concerns',
                        [],
                        $user->language
                    ) . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    /*'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y') . ' - ' .
                        Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),*/
                    'title' => __(
                        'notification.keyWords.concerns_time_period',
                        [
                            'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y'),
                            'end' => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y')
                        ],
                        $user->language
                    ),
                    'href' => null
                ],
            ]);
            $notificationService->setNotificationTo($user);
            $notificationService->createNotification();

            // send same notification to admin

            $notificationService->setPriority(1);
            $notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $notificationService->setButtons(['see_shift', 'delete_shift_notification']);

            foreach (User::role(RoleEnum::ARTWORK_ADMIN->value)->get() as $adminUser) {
                $notificationTitle = __('notification.shift.worker_short_break', [], $adminUser->language);
                $notificationService->setTitle($notificationTitle);
                $notificationService->setDescription([
                    1 => [
                        'type' => 'string',
                        'title' => __(
                            'notification.keyWords.concerns',
                            [],
                            $adminUser->language
                        ) . $user->getFullNameAttribute(),
                        'href' => null
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => __(
                            'notification.keyWords.concerns_time_period',
                            [
                                'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)
                                    ->format('d.m.Y'),
                                'end' => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)
                                    ->format('d.m.Y')
                            ],
                            $adminUser->language
                        ),
                        'href' => null
                    ],
                ]);
                $notificationService->setNotificationTo($adminUser);
                $notificationService->createNotification();
            }

            $usersWhichGotNotification = [];
            foreach ($user->crafts as $craft) {
                foreach ($craft->users as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    if (in_array($craftUser->id, $usersWhichGotNotification)) {
                        continue;
                    }
                    $notificationTitle = __('notification.shift.worker_short_break', [], $craftUser->language);
                    $notificationService->setTitle($notificationTitle);
                    $notificationService->setDescription([
                        1 => [
                            'type' => 'string',
                            'title' => __(
                                'notification.keyWords.concerns',
                                [],
                                $craftUser->language
                            ) . $user->getFullNameAttribute(),
                            'href' => null
                        ],
                        2 => [
                            'type' => 'string',
                            'title' => __(
                                'notification.keyWords.concerns_time_period',
                                [
                                    'start' => Carbon::parse($shiftBreakCheck->firstShift->event_start_day)
                                        ->format('d.m.Y'),
                                    'end' => Carbon::parse($shiftBreakCheck->lastShift->event_start_day)
                                        ->format('d.m.Y')
                                ],
                                $craftUser->language
                            ),
                            'href' => null
                        ],
                    ]);
                    $notificationService->setNotificationTo($craftUser);
                    $notificationService->createNotification();
                    $usersWhichGotNotification[] = $craftUser->id;
                }
            }
            $notificationService->clearNotificationData();
        }
    }

    private function checkUserInMoreThanTenShiftsAndCreateNotificationsIfNecessary(
        Shift $shift,
        User $user,
        NotificationService $notificationService
    ): void {
        $shiftCheck = $notificationService->checkIfUserInMoreThanTenShifts($user, $shift);

        if ($shiftCheck->moreThanTenShifts) {
            $notificationTitle = __('notification.shift.more_than_ten_days', [], $user->language);
            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('red');
            $notificationService->setPriority(2);
            $notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $notificationService->setBroadcastMessage([
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ]);
            $notificationService->setDescription([
                1 => [
                    'type' => 'string',
                    'title' => __(
                        'notification.keyWords.concerns',
                        [],
                        $user->language
                    ) . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => __(
                        'notification.keyWords.concerns_time_period',
                        [
                            'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)
                                ->format('d.m.Y'),
                            'end' => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)
                                ->format('d.m.Y')
                        ],
                        $user->language
                    ),
                    'href' => null
                ],
            ]);

            $notificationService->setNotificationTo($user);
            $notificationService->createNotification();

            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('blue');
            $notificationService->setPriority(1);
            $notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT);

            $notificationService->setButtons(['see_shift', 'delete_shift_notification']);

            foreach (User::role(RoleEnum::ARTWORK_ADMIN->value)->get() as $adminUser) {
                $notificationTitle = __('notification.shift.worker_more_than_ten_days', [], $adminUser->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __(
                            'notification.keyWords.concerns',
                            [],
                            $user->language
                        ) . $user->getFullNameAttribute(),
                        'href' => null
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => __(
                            'notification.keyWords.concerns_time_period',
                            [
                                'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)
                                    ->format('d.m.Y'),
                                'end' => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)
                                    ->format('d.m.Y')
                            ],
                            $user->language
                        ),
                        'href' => null
                    ],
                ];
                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setDescription($notificationDescription);
                $notificationService->setNotificationTo($adminUser);
                $notificationService->createNotification();
            }

            $usersWhichGotNotification = [];
            foreach ($user->crafts as $craft) {
                foreach ($craft->users as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    if (in_array($craftUser->id, $usersWhichGotNotification)) {
                        continue;
                    }
                    $notificationTitle = __('notification.shift.worker_more_than_ten_days', [], $craftUser->language);
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __(
                                'notification.keyWords.concerns',
                                [],
                                $craftUser->language
                            ) . $user->getFullNameAttribute(),
                            'href' => null
                        ],
                        2 => [
                            'type' => 'string',
                            'title' => __(
                                'notification.keyWords.concerns_time_period',
                                [
                                    'start' => Carbon::parse($shiftCheck->firstShift->first()->event_start_day)
                                        ->format('d.m.Y'),
                                    'end' => Carbon::parse($shiftCheck->lastShift->first()->event_start_day)
                                        ->format('d.m.Y')
                                ],
                                $craftUser->language
                            ),
                            'href' => null
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

        $notificationService->clearNotificationData();
    }

    private function handleSeriesShiftData(
        Shift $shift,
        Carbon $start,
        Carbon $end,
        string $dayOfWeek,
        int $userId,
        int $shiftQualificationId,
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
                //same shift is found, user already assigned, continue
                $shiftBetweenDates->id === $shift->id ||
                //if day of week is given and is not "all" compare it to shift, if not matching continue
                (
                    $dayOfWeek !== 'all' &&
                    Carbon::parse($shiftBetweenDates->event_start_day)->dayOfWeek !== ((int) $dayOfWeek)
                ) ||
                //if user already assigned to shift continue
                $shiftBetweenDates->users()
                    ->get(['users.id'])
                    ->pluck('id')
                    ->contains($userId)
            ) {
                continue;
            }

            //get value of shifts qualifications by shiftQualificationId and shiftId to determine how many users
            //can be assigned in total
            $shiftsQualificationsValue = $this->shiftsQualificationsRepository->findByShiftIdAndShiftQualificationId(
                $shiftBetweenDates->id,
                $shiftQualificationId
            )?->value;

            //if shiftsQualifications value is null or 0 continue
            if ($shiftsQualificationsValue === null || $shiftsQualificationsValue === 0) {
                continue;
            }

            //determine if a slot is available, get all shift_user, shifts_freelancers and shifts_service_providers
            //entries containing shiftId and shiftQualificationId and count them
            if (
                $this->getWorkerCountForQualificationByShiftIdAndShiftQualificationId(
                    $shiftBetweenDates->id,
                    $shiftQualificationId
                ) < $shiftsQualificationsValue
            ) {
                //call assignToShift without seriesShiftData to make sure only this user is assigned to shift and same
                //logic is applied for each user
                $this->assignToShift(
                    $shiftBetweenDates,
                    $userId,
                    $shiftQualificationId,
                    $notificationService,
                    $shiftCountService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
            }
        }
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

    public function removeFromShift(
        ShiftUser|int $usersPivot,
        bool $removeFromSingleShift,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $shiftUserPivot = !$usersPivot instanceof ShiftUser ?
            $this->shiftUserRepository->getById($usersPivot) :
            $usersPivot;

        /** @var Shift $shift */
        $shift = $shiftUserPivot->shift;
        /** @var User $user */
        $user = $shiftUserPivot->user;

        $this->forceDelete($shiftUserPivot);
        $shiftCountService->handleShiftUsersShiftCount($shift, $user->id);

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

        if (!$removeFromSingleShift) {
            foreach ($this->shiftRepository->getShiftsByUuid($shift->shift_uuid) as $shiftByUuid) {
                if ($shiftByUuid->id === $shift->id) {
                    continue;
                }

                //find additional shift user pivot by shift and given user id, if found call this function again
                //with removeFromSingleShift set to true making sure same logic is applied for each pivot which is
                //deleted
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
    }

    public function removeAllUsersFromShift(
        Shift $shift,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $shift->users()->each(
            function (User $user) use (
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            ): void {
                //call remove from shift with removeFromSingleShift set to true making sure same logic is applied
                //for each pivot which is deleted
                $this->removeFromShift(
                    $user->pivot,
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
        int $userId,
        int $shiftId,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $this->removeFromShift(
            $this->shiftUserRepository->findByUserIdAndShiftId(
                $userId,
                $shiftId
            ),
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
                    $shift->event->eventName
                ])
        );

        $notificationService->setProjectId($shift->event->project->id);
        $notificationService->setEventId($shift->event->id);
        $notificationService->setShiftId($shift->id);
        $notificationTitle = __(
            'notification.shift.shift_staffing_deleted',
            [
                'projectName' => $shift->event->project->name,
                'craftAbbreviation' => $shift->craft->abbreviation
            ],
            $user->language
        );
        $notificationService->setTitle($notificationTitle);
        $notificationService->setIcon('red');
        $notificationService->setPriority(2);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);
        $notificationService->setBroadcastMessage([
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ]);
        $notificationService->setDescription([
            1 => [
                'type' => 'string',
                'title' => __('notification.keyWords.concerns_shift', [], $user->language) .
                    Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
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
}
