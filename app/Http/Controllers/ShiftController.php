<?php

namespace App\Http\Controllers;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftCountService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Random\RandomException;

class ShiftController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService,
        private readonly AvailabilityConflictService $availabilityConflictService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly ShiftService $shiftService,
        private readonly EventService $eventService
    ) {
    }

    /**
     * @throws RandomException
     */
    public function store(
        Request $request,
        Event $event,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        if ($request->automaticMode) {
            $shift = $this->shiftService->createAutomatic(
                event: $event,
                craftId: $request->craft_id,
                data: $request->all(),
            );
        } else {
            $shift = $this->shiftService->createShiftByRequest($request->all(), $event);
        }

        $shift->update([
            'event_start_day' => Carbon::parse($event->start_time)->format('Y-m-d'),
            'event_end_day' => Carbon::parse($event->end_time)->format('Y-m-d'),
        ]);

        foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
            $shiftsQualificationsService->createShiftsQualificationForShift($shift->id, $shiftsQualification);
        }

        $shiftUuid = Str::uuid();

        if ($request->changeAll) {
            $start = Carbon::parse($request->changes_start)->startOfDay();
            $end = Carbon::parse($request->changes_end)->endOfDay();
            $seriesEvents = Event::where('series_id', $event->series_id)
                ->where(function ($query) use ($start, $end): void {
                    $query->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end]);
                })
                ->get();

            /** @var Event $seriesEvent */
            foreach ($seriesEvents as $seriesEvent) {
                if ($seriesEvent->id != $event->id) {
                    $newShift = $this->shiftService->createShiftBySeriesEvent(
                        $seriesEvent,
                        $request->all(),
                        $request->craft_id
                    );

                    $newShift->update([
                        'shift_uuid' => $shiftUuid,
                        'event_start_day' => Carbon::parse($seriesEvent->start_time)->format('Y-m-d'),
                        'event_end_day' => Carbon::parse($seriesEvent->end_time)->format('Y-m-d'),
                    ]);
                    foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
                        $shiftsQualificationsService->createShiftsQualificationForShift(
                            $newShift->id,
                            $shiftsQualification
                        );
                    }
                }
            }
        }

        if ($event->is_series) {
            $shift->update([
                'shift_uuid' => $shiftUuid,
            ]);
        }

        if ($shift->infringement) {
            $this->shiftService->createInfringementNotification($shift);
        }

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('shift')
                ->setModelClass(Shift::class)
                ->setModelId($shift->id)
                ->setShift($shift)
                ->setTranslationKey('Shift of event was created')
                ->setTranslationKeyPlaceholderValues([$event->eventName])
        );
    }

    public function show(): void
    {
    }

    public function edit(): void
    {
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        if ($shift->is_committed) {
            $event = $shift->event;

            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Shift of event has been edited')
                    ->setTranslationKeyPlaceholderValues([$event->eventName])
            );
        }

        $shift->update($request->only([
            'start_date',
            'end_date',
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

        return Redirect::route('shifts.plan');
    }

    public function updateShift(
        Request $request,
        Shift $shift,
        ShiftsQualificationsService $shiftsQualificationsService,
        ProjectTabService $projectTabService
    ): RedirectResponse {
        $projectId =  $shift->event()->first()->project()->first()->id;
        if ($shift->is_committed) {
            $event = $shift->event;

            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Shift of event has been edited')
                    ->setTranslationKeyPlaceholderValues([$event->eventName])
            );

            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);

            foreach ($shift->users()->get() as $user) {
                $notificationTitle = __(
                    'notification.shift.locked_changes',
                    [
                    'projectName' => $shift->event()->first()->project()->first()->name,
                    'craftAbbreviation' => $shift->craft()->first()->abbreviation
                    ],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.keyWords.concerns_shift', [], $user->language) .
                            Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                            Carbon::parse($shift->end)->format('d.m.Y H:i'),
                        'href' => null
                    ],
                ];

                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }

            $craft = $shift->craft()->first();

            foreach ($craft->users()->get() as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $notificationTitle = __(
                        'notification.shift.locked_changes',
                        [
                            'projectName' => $shift->event()->first()->project()->first()->name,
                            'craftAbbreviation' => $shift->craft()->first()->abbreviation
                        ],
                        $craftUser->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.keyWords.concerns_shift', [], $user->language) .
                                Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                                Carbon::parse($shift->end)->format('d.m.Y H:i'),
                            'href' => null
                        ],
                    ];

                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
        }

        $shift->update($request->only([
            'start_date',
            'end_date',
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

        foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
            $shiftsQualificationsService->updateShiftsQualificationForShift($shift->id, $shiftsQualification);
        }

        if ($projectTab = $projectTabService->findFirstProjectTabWithShiftsComponent()) {
            return Redirect::route('projects.tab', [$projectId, $projectTab->id]);
        }

        return Redirect::back();
    }

    private function sendShiftAddedNotificationToUser(Shift $shift, User $user): void
    {
        $notificationTitle = __(
            'notification.shift.shift_staffing',
            [
                'projectName' => $shift->event()->first()->project()->first()->name,
                'craftAbbreviation' => $shift->craft()->first()->abbreviation
            ],
            $user->language
        );
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => __('notification.keyWords.your_shift', [], $user->language) .
                    Carbon::parse($shift->start)
                        ->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
            ],
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();
    }

    private function setConflictNotificationHeaderAndData(Shift $shift): void
    {
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CONFLICT);
        $this->notificationService->setButtons(['see_shift']);
        $this->notificationService->setShiftId($shift->id);
    }

    //@todo: fix phpcs error - complexity too high, nesting too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function updateCommitments(Request $request, ProjectTabService $projectTabService): RedirectResponse
    {
        $projectId = $request->input('project_id');
        $shiftIds = $request->input('shifts');
        $updateData = $request->only([
            'is_committed',
            'committing_user_id'
        ]);

        $notificationUsers = [];

        $shifts = Shift::whereIn('id', $shiftIds)->get();

        foreach ($shifts as $shift) {
            $shift->update($updateData);
            $shiftCommittedBy = $shift->committedBy()->first();
            if ($shift->is_committed) {
                $users = $shift->users()->get();
                foreach ($users as $user) {
                    if (!in_array($user->id, $notificationUsers)) {
                        $this->sendShiftAddedNotificationToUser(
                            shift: $shift,
                            user: $user
                        );
                        $notificationUsers[] = $user->id;
                    }


                    $vacations = $user
                        ->vacations()
                        ->get();
                    $availabilities = $user
                        ->availabilities()
                        ->get();

                    $this->setConflictNotificationHeaderAndData($shift);

                    if ($vacations->count() > 0) {
                        foreach ($vacations as $vacation) {
                            // check if vacation is full_day
                            if ($vacation->full_day) {
                                $this->vacationConflictService->create([
                                    'vacation_id' => $vacation->id,
                                    'shift_id' => $shift->id,
                                    'user_name' => $shiftCommittedBy->full_name,
                                    'date' => $shift->event_start_day,
                                    'start_time' => $shift->start,
                                    'end_time' => $shift->end,
                                ]);
                                $notificationTitle = __(
                                    'notification.shift.conflict',
                                    [],
                                    $user->language
                                );
                                $broadcastMessage = [
                                    'id' => rand(1, 1000000),
                                    'type' => 'success',
                                    'message' => $notificationTitle
                                ];
                                $notificationDescription = [
                                    1 => [
                                        'type' => 'string',
                                        /*'title' => $shiftCommittedBy->full_name . ' hat dich am ' .
                                            Carbon::parse($shift->event_start_day)->format('d.m.Y') . ' ' .
                                            $shift->start . ' - ' . $shift->end
                                            . ' eingeplant, entgegen deines ursprünglichen Eintrags.',*/
                                        'title' => __(
                                            'notification.shift.conflict_text',
                                            [
                                                'username' => $shiftCommittedBy->full_name,
                                                'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                                                'from' => $shift->start,
                                                'to' => $shift->end
                                            ],
                                            $user->language
                                        ),
                                        'href' => null
                                    ],
                                ];

                                $this->notificationService->setTitle($notificationTitle);
                                $this->notificationService->setBroadcastMessage($broadcastMessage);
                                $this->notificationService->setDescription($notificationDescription);
                                $this->notificationService->setNotificationTo($user);
                                $this->notificationService->createNotification();
                            } else {
                                // check if shift is on vacation time
                                $start = Carbon::parse($vacation->start_time);
                                $end = Carbon::parse($vacation->end_time);
                                if (
                                    $start->between($shift->start, $shift->end) ||
                                    $end->between($shift->start, $shift->end)
                                ) {
                                    $this->vacationConflictService->create([
                                        'vacation_id' => $vacation->id,
                                        'shift_id' => $shift->id,
                                        'user_name' => $shiftCommittedBy->full_name,
                                        'date' => $shift->event_start_day,
                                        'start_time' => $shift->start,
                                        'end_time' => $shift->end,
                                    ]);
                                    $notificationTitle = __(
                                        'notification.shift.conflict',
                                        [],
                                        $user->language
                                    );
                                    $broadcastMessage = [
                                        'id' => rand(1, 1000000),
                                        'type' => 'success',
                                        'message' => $notificationTitle
                                    ];
                                    $notificationDescription = [
                                        1 => [
                                            'type' => 'string',
                                            'title' => __(
                                                'notification.shift.conflict_text',
                                                [
                                                    'username' => $shiftCommittedBy->full_name,
                                                    'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                                                    'from' => $shift->start,
                                                    'to' => $shift->end
                                                ],
                                                $user->language
                                            ),
                                            'href' => null
                                        ],
                                    ];

                                    $this->notificationService->setTitle($notificationTitle);
                                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                                    $this->notificationService->setDescription($notificationDescription);
                                    $this->notificationService->setNotificationTo($user);
                                    $this->notificationService->createNotification();
                                }
                            }
                        }
                    }

                    if ($availabilities->count() > 0) {
                        foreach ($availabilities as $availability) {
                            // check if shift is before or after availability time
                            $shiftStart = Carbon::parse($shift->start);
                            $shiftEnd = Carbon::parse($shift->end);
                            $availabilityStart = Carbon::parse($availability->start_time);
                            $availabilityEnd = Carbon::parse($availability->end_time);

                            if (
                                $shiftEnd->lessThanOrEqualTo($availabilityStart) ||
                                $shiftStart->greaterThanOrEqualTo($availabilityEnd)
                            ) {
                                $this->availabilityConflictService->create([
                                    'availability_id' => $availability->id,
                                    'shift_id' => $shift->id,
                                    'user_name' => $shiftCommittedBy->full_name,
                                    'date' => $shift->event_start_day,
                                    'start_time' => $shift->start,
                                    'end_time' => $shift->end,
                                ]);
                                $notificationTitle = __(
                                    'notification.shift.conflict',
                                    [],
                                    $user->language
                                );
                                $broadcastMessage = [
                                    'id' => rand(1, 1000000),
                                    'type' => 'success',
                                    'message' => $notificationTitle
                                ];
                                $notificationDescription = [
                                    1 => [
                                        'type' => 'string',
                                        'title' => __(
                                            'notification.shift.conflict_text',
                                            [
                                                'username' => $shiftCommittedBy->full_name,
                                                'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                                                'from' => $shift->start,
                                                'to' => $shift->end
                                            ],
                                            $user->language
                                        ),
                                        'href' => null
                                    ],
                                ];

                                $this->notificationService->setTitle($notificationTitle);
                                $this->notificationService->setBroadcastMessage($broadcastMessage);
                                $this->notificationService->setDescription($notificationDescription);
                                $this->notificationService->setNotificationTo($user);
                                $this->notificationService->createNotification();
                            } else {
                                $availability->conflicts()->each(function ($conflict): void {
                                    $conflict->delete();
                                });
                            }
                        }
                    }
                }
            } else {
                $shift->update([
                    'committing_user_id' => null
                ]);
                $vacationsConflict = VacationConflict::where('shift_id', $shift->id)->get();
                foreach ($vacationsConflict as $vacationConflict) {
                    $vacationConflict->delete();
                }
                $availabilitiesConflict = AvailabilitiesConflict::where('shift_id', $shift->id)->get();
                foreach ($availabilitiesConflict as $availabilityConflict) {
                    $availabilityConflict->delete();
                }
            }
        }

        if ($projectTab = $projectTabService->findFirstProjectTabWithShiftsComponent()) {
            return Redirect::route('projects.tab', [$projectId, $projectTab->id]);
        }

        return Redirect::back();
    }

    public function destroy(Shift $shift): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;

            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Shift of event was deleted')
                    ->setTranslationKeyPlaceholderValues([$event->eventName])
            );

            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);

            foreach ($shift->users()->get() as $user) {
                if (Auth::id() !== $user->id) {
                    $notificationTitle = __(
                        'notification.shift.deleted_where_locked',
                        [
                            'projectName' => $shift->event()->first()->project()->first()->name,
                            'craftAbbreviation' => $shift->craft()->first()->abbreviation
                        ],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.shift.concerns_shift', [], $user->language)
                                . Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                                Carbon::parse($shift->end)->format('d.m.Y H:i'),
                            'href' => null
                        ],
                    ];

                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                }
            }

            $craft = $shift->craft()->first();

            foreach ($craft->users()->get() as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $notificationTitle = __(
                        'notification.shift.deleted_where_locked',
                        [
                            'projectName' => $shift->event()->first()->project()->first()->name,
                            'craftAbbreviation' => $shift->craft()->first()->abbreviation
                        ],
                        $craftUser->language
                    );
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.shift.concerns_shift', [], $craftUser->language) .
                                Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                                Carbon::parse($shift->end)->format('d.m.Y H:i'),
                            'href' => null
                        ],
                    ];

                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }

            // conflicts
            $conflicts = VacationConflict::where('shift_id', $shift->id)->get();
            $conflictsAvailability = AvailabilitiesConflict::where('shift_id', $shift->id)->get();

            $conflicts->each(function ($conflict): void {
                $conflict->delete();
            });

            $conflictsAvailability->each(function ($conflict): void {
                $conflict->delete();
            });
        }

        $this->shiftService->forceDelete($shift);
    }

    //phpcs:ignore
    public function saveMultiEdit(
        Request $request,
        ShiftService $shiftService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): void {
        $shiftsToHandle = $request->get('shiftsToHandle', ['assignToShift' => [], 'removeFromShift' => []]);

        if (empty($shiftsToHandle['assignToShift']) && empty($shiftsToHandle['removeFromShift'])) {
            return;
        }

        $serviceToUse = match ($request->get('userType')) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return;
        }

        foreach ($shiftsToHandle['removeFromShift'] as $shiftIdToRemove) {
            if ($serviceToUse instanceof ShiftServiceProviderService) {
                $serviceToUse->removeFromShiftByUserIdAndShiftId(
                    $request->get('userTypeId'),
                    $shiftIdToRemove,
                    $shiftCountService,
                    $changeService
                );

                continue;
            }

            $serviceToUse->removeFromShiftByUserIdAndShiftId(
                $request->get('userTypeId'),
                $shiftIdToRemove,
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }

        foreach ($shiftsToHandle['assignToShift'] as $shiftToAssign) {
            $shift = $shiftService->getById($shiftToAssign['shiftId']);

            if (!$shift instanceof Shift) {
                continue;
            }

            if ($serviceToUse instanceof ShiftServiceProviderService) {
                $serviceToUse->assignToShift(
                    $shift,
                    $request->get('userTypeId'),
                    $shiftToAssign['shiftQualificationId'],
                    $shiftCountService,
                    $changeService
                );

                continue;
            }

            $serviceToUse->assignToShift(
                $shift,
                $request->get('userTypeId'),
                $shiftToAssign['shiftQualificationId'],
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );
        }
    }

    public function assignToShift(
        Shift $shift,
        Request $request,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService,
    ): RedirectResponse {
        $serviceToUse = match ($request->get('userType')) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return Redirect::back();
        }

        if ($serviceToUse instanceof ShiftServiceProviderService) {
            $serviceToUse->assignToShift(
                $shift,
                $request->get('userId'),
                $request->get('shiftQualificationId'),
                $shiftCountService,
                $changeService,
                $request->get('seriesShiftData')
            );

            return Redirect::back();
        }

        $serviceToUse->assignToShift(
            $shift,
            $request->get('userId'),
            $request->get('shiftQualificationId'),
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService,
            $request->get('seriesShiftData')
        );

        return Redirect::back();
    }

    public function removeFromShift(
        int $usersPivotId,
        int $userType,
        Request $request,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): RedirectResponse {
        $serviceToUse = match ($userType) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return Redirect::back();
        }

        if ($serviceToUse instanceof ShiftServiceProviderService) {
            $serviceToUse->removeFromShift(
                $usersPivotId,
                $request->boolean('removeFromSingleShift'),
                $shiftCountService,
                $changeService
            );

            return Redirect::back();
        }

        $serviceToUse->removeFromShift(
            $usersPivotId,
            $request->boolean('removeFromSingleShift'),
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );

        return Redirect::back();
    }

    public function removeAllShiftUsers(
        Shift $shift,
        ShiftService $shiftService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): RedirectResponse {
        $shiftUserService->removeAllUsersFromShift(
            $shift,
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );
        $shiftFreelancerService->removeAllFreelancersFromShift(
            $shift,
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );
        $shiftServiceProviderService->removeAllServiceProvidersFromShift($shift, $shiftCountService, $changeService);

        if ($shift->is_committed) {
            $shiftService->createRemovedAllUsersFromShiftHistoryEntry($shift, $changeService);
        }

        return Redirect::back();
    }

    public function updateDescription(Request $request, Shift $shift): RedirectResponse
    {
        $shift->update($request->only(['description']));

        return Redirect::back();
    }
}
