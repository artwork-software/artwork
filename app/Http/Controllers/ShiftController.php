<?php

namespace App\Http\Controllers;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventTimelineService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\IndividualTimes\Services\IndividualTimeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\AssignUserToShift;
use Artwork\Modules\Shift\Events\CreatedShiftInShiftPlan;
use Artwork\Modules\Shift\Events\DestroyShift;
use Artwork\Modules\Shift\Events\MultiShiftCreateInShiftPlan;
use Artwork\Modules\Shift\Events\RemoveEntityFormShiftEvent;
use Artwork\Modules\Shift\Events\UpdateEventShiftInShiftPlan;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\Shift\Services\ShiftCountService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Shift\Services\ShiftPlanCommentService;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Vacation\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        private readonly Redirector $redirector,
        private readonly IndividualTimeService $individualTimeService,
        private readonly ShiftPlanCommentService $shiftPlanCommentService,
        private readonly VacationService $vacationService,
        private readonly EventTimelineService $eventTimelineService,
        private readonly EventService $eventService,
        private readonly GeneralSettings $generalSettings,
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

        $shift = $this->shiftService->createAutomatic(
            event: $event,
            craftId: $request->craft_id,
            data: $request->all(),
        );

        $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);

        $shift->event_start_day = Carbon::parse($event->start_time)->format('Y-m-d');
        $shift->event_end_day = Carbon::parse($event->end_time)->format('Y-m-d');

        $this->shiftService->save($shift);
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

                    $newShift->shift_uuid = $shiftUuid;
                    $newShift->event_start_day = Carbon::parse($seriesEvent->start_time)->format('Y-m-d');
                    $newShift->event_end_day = Carbon::parse($seriesEvent->end_time)->format('Y-m-d');
                    $this->shiftService->save($newShift);
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
            $shift->shift_uuid = $shiftUuid;
            $this->shiftService->save($shift);
        }

        if ($shift->infringement) {
            $this->shiftService->createInfringementNotification($shift);
        }

        if ($event?->exists) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('shift')
                    ->setModelClass(Shift::class)
                    ->setModelId($shift->id)
                    ->setShift($shift)
                    ->setTranslationKey('Shift of event was created')
                    ->setTranslationKeyPlaceholderValues([$event?->eventName])
            );
        }

        broadcast(new UpdateEventShiftInShiftPlan(
            $shift->load([
                'craft',
                'users',
                'freelancer',
                'serviceProvider',
                'committedBy'
            ]),
            $shift?->event?->room_id ?? $shift?->room_id,
        ));
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

            if ($event?->exists) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('shift')
                        ->setModelClass(Shift::class)
                        ->setModelId($shift->id)
                        ->setShift($shift)
                        ->setTranslationKey('Shift of event has been edited')
                        ->setTranslationKeyPlaceholderValues([$event?->eventName])
                );
            }
        }

        $shift->fill($request->only([
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

        $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);

        $this->shiftService->save($shift);



        return $this->redirector->route('shifts.plan');
    }

    public function updateShift(
        Request $request,
        Shift $shift,
        ShiftsQualificationsService $shiftsQualificationsService,
        ProjectTabService $projectTabService
    ): RedirectResponse {
        $projectId = $shift?->project_id;
        if ($shift->is_committed) {
            $event = $shift?->event;

            if ($event?->exists) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('shift')
                        ->setModelClass(Shift::class)
                        ->setModelId($shift->id)
                        ->setShift($shift)
                        ->setTranslationKey('Shift of event has been edited')
                        ->setTranslationKeyPlaceholderValues([$event?->eventName])
                );
            }

            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);

            foreach ($shift->users()->get() as $user) {
                $notificationTitle = __(
                    'notification.shift.locked_changes',
                    [
                        'projectName' => $shift?->event?->project?->name ?? __('notification.shift.without_project'),
                        'craftAbbreviation' => $shift->craft->abbreviation
                    ],
                    $user?->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.keyWords.concerns_shift', [], $user?->language) .
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

            /** @var User $craftUser */
            foreach ($craft->users()->get() as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $notificationTitle = __(
                        'notification.shift.locked_changes',
                        [
                            'projectName' => $shift?->event?->project?->name ??
                                __('notification.shift.without_project'),
                            'craftAbbreviation' => $shift->craft->abbreviation
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
                            'title' => __('notification.keyWords.concerns_shift', [], $craftUser?->language) .
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

        $shift->fill($request->only([
            'start_date',
            'end_date',
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
            'project_id',
            'shift_group_id',
        ]));

        $this->shiftService->save($shift);

        if (!$request->filled('shiftsQualifications') || empty($request->get('shiftsQualifications'))) {

            ShiftUser::where('shift_id', $shift->id)->forceDelete();
            ShiftFreelancer::where('shift_id', $shift->id)->forceDelete();
            ShiftServiceProvider::where('shift_id', $shift->id)->forceDelete();


            if ($shift->shiftsQualifications()->exists()) {
                $shift->shiftsQualifications()->delete();
            }

            // 3) Eager-Loaded Relation invalidieren, damit Response nicht alte Daten zeigt
            $shift->unsetRelation('users');
        }


        foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
            $shiftsQualificationsService->updateShiftsQualificationForShift($shift->id, $shiftsQualification);
        }

        $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);

        $projectTab = $projectTabService->findFirstProjectTabWithShiftsComponent();

        if ($shift->event_id) {
            broadcast(new UpdateEventShiftInShiftPlan($shift, $shift->event->room_id));
        } else {
            broadcast(new UpdateShiftInShiftPlan($shift, $shift->room_id));
        }


        if ($projectTab && $projectId && !$request->boolean('updateOrCreateInShiftPlan')) {
            return $this->redirector->route('projects.tab', [$projectId, $projectTab->id]);
        }


        return $this->redirector->back();
    }

    private function sendShiftAddedNotificationToUser(Shift $shift, User $user): void
    {
        $notificationTitle = __(
            'notification.shift.shift_staffing',
            [
                'projectName' => $shift?->event?->project?->name ?? __('notification.shift.without_project'),
                'craftAbbreviation' => $shift->craft->abbreviation
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

    public function updateTime(Request $request, Shift $shift): void
    {
        [$start, $end] = $this->eventService->processEventTimesForTimeline(
            Carbon::parse($shift->start_date),
            $request->get('start') ?? null,
            $request->get('end') ?? null
        );

        $shift->start_date = Carbon::parse($start)->format('Y-m-d');
        $shift->end_date = Carbon::parse($end)->format('Y-m-d');
        $shift->start = Carbon::parse($start)->format('H:i:s');
        $shift->end = Carbon::parse($end)->format('H:i:s');
        $shift->break_minutes = $request->get('break_minutes');

        $shift->save();
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
            return $this->redirector->route('projects.tab', [$projectId, $projectTab->id]);
        }

        return $this->redirector->back();
    }

    public function destroy(Shift $shift): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;

            if ($event?->exists) {
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
            }

            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);

            foreach ($shift->users()->get() as $user) {
                if (Auth::id() !== $user->id) {
                    $notificationTitle = __(
                        'notification.shift.deleted_where_locked',
                        [
                            'projectName' => $shift?->event?->project?->name ??
                                __('notification.shift.without_project'),
                            'craftAbbreviation' => $shift->craft->abbreviation
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
                            'projectName' => $shift?->event?->project?->name ??
                                __('notification.shift.without_project'),
                            'craftAbbreviation' => $shift->craft->abbreviation
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
        if ($shift->event_id) {
            broadcast(new DestroyShift(
                $shift,
                $shift->event->room_id
            ));
        } else {
            broadcast(new DestroyShift(
                $shift,
                $shift->room_id
            ));
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
    ): bool {
        $shiftsToHandle = $request->get('shiftsToHandle', ['assignToShift' => [], 'removeFromShift' => []]);
        if (empty($shiftsToHandle['assignToShift']) && empty($shiftsToHandle['removeFromShift'])) {
            return false;
        }

        $serviceToUse = match ($request->get('userType')) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return false;
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
            $shift = $shiftService->getById($shiftIdToRemove);

            broadcast(new RemoveEntityFormShiftEvent(
                $shift->load([
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'committedBy'
                ]),
                $shift?->room_id ?? $shift?->event?->room_id,
                $request->get('userTypeId'),
                $request->get('userType')
            ));
        }

        foreach ($shiftsToHandle['assignToShift'] as $shiftToAssign) {
            $shift = $shiftService->getById($shiftToAssign['shiftId']);

            if (!$shift instanceof Shift) {
                continue;
            }

            // Resolve a valid shift qualification id if not provided
            $resolvedShiftQualificationId = $shiftToAssign['shiftQualificationId'] ?? null;
            if ($resolvedShiftQualificationId === null) {
                // Try to take the first defined qualification for this shift
                $resolvedShiftQualificationId = $shift->shiftsQualifications()->orderBy('id')->value('shift_qualification_id');
                if ($resolvedShiftQualificationId === null) {
                    // Fallback to a generic worker qualification if available
                    $resolvedShiftQualificationId = \Artwork\Modules\Shift\Models\ShiftQualification::available()
                        ->workerQualification()
                        ->orderByCreationDateAscending()
                        ->value('id');
                }
            }

            // If no qualification id can be resolved, skip this assignment entry (DB requires it)
            if (!$resolvedShiftQualificationId) {
                continue;
            }

            if ($serviceToUse instanceof ShiftServiceProviderService) {
                $serviceToUse->assignToShift(
                    $shift,
                    $request->get('userTypeId'),
                    $resolvedShiftQualificationId,
                    $request->string('craft_abbreviation'),
                    $shiftCountService,
                    $changeService
                );

                broadcast(new AssignUserToShift(
                    $shift->load([
                        'craft',
                        'users',
                        'freelancer',
                        'serviceProvider',
                        'committedBy'
                    ]),
                    $shift?->room_id ?? $shift?->event?->room_id,
                    $request->get('userTypeId'),
                    $request->get('userType')
                ));

                continue;
            }

            $serviceToUse->assignToShift(
                $shift,
                $request->get('userTypeId'),
                $resolvedShiftQualificationId,
                $request->string('craft_abbreviation'),
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );

            broadcast(new AssignUserToShift(
                $shift->load([
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'committedBy'
                ]),
                $shift?->room_id ?? $shift?->event?->room_id,
                $request->get('userTypeId'),
                $request->get('userType')
            ));
        }

        return true;
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
    ): bool|RedirectResponse {

        $isShiftTab = $request->boolean('isShiftTab');
        $serviceToUse = match ($request->get('userType')) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return $isShiftTab ? $this->redirector->back() : false;
        }

        if ($serviceToUse instanceof ShiftServiceProviderService) {
            $serviceToUse->assignToShift(
                $shift,
                $request->get('userId'),
                $request->get('shiftQualificationId'),
                $request->string('craft_abbreviation'),
                $shiftCountService,
                $changeService,
                $request->get('seriesShiftData')
            );

            if (!$shift->event_id) {
                broadcast(new AssignUserToShift(
                    $shift->load([
                        'craft',
                        'users',
                        'freelancer',
                        'serviceProvider',
                        'committedBy'
                    ]),
                    $shift->room_id,
                    $request->get('userId'),
                    $request->get('userType')
                ));
            } else {
                broadcast(new AssignUserToShift(
                    $shift->load([
                        'craft',
                        'users',
                        'freelancer',
                        'serviceProvider',
                        'committedBy'
                    ]),
                    $shift->event->room_id,
                    $request->get('userId'),
                    $request->get('userType')
                ));
            }

            return $isShiftTab ? $this->redirector->back() : true;
        }

        $serviceToUse->assignToShift(
            $shift,
            $request->get('userId'),
            $request->get('shiftQualificationId'),
            $request->string('craft_abbreviation'),
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService,
            $request->get('seriesShiftData')
        );


        if (!$shift->event_id) {
            broadcast(new AssignUserToShift(
                $shift->load([
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'committedBy'
                ]),
                $shift->room_id,
                $request->get('userId'),
                $request->get('userType')
            ));
        } else {
            broadcast(new AssignUserToShift(
                $shift->load([
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'committedBy'
                ]),
                $shift->event->room_id,
                $request->get('userId'),
                $request->get('userType')
            ));
        }

        return $isShiftTab ? $this->redirector->back() : true;
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
    ): bool|RedirectResponse|null {
        $isShiftTab = $request->boolean('isShiftTab');
        $serviceToUse = match ($userType) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return $isShiftTab ? $this->redirector->back() : null;
        }

        // Check if the pivot record still exists before proceeding
        try {
            $shift = $serviceToUse->getShiftByUserPivotId($usersPivotId);
        } catch (\Exception $e) {
            // Pivot record doesn't exist anymore (likely already deleted)
            // Return success response since the goal (removing user from shift) is already achieved
            return $isShiftTab ? $this->redirector->back() : null;
        }

        // Additional check in case the method returns null instead of throwing exception
        if (!$shift) {
            return $isShiftTab ? $this->redirector->back() : null;
        }

        if ($serviceToUse instanceof ShiftServiceProviderService) {
            $serviceToUse->removeFromShift(
                $usersPivotId,
                $request->boolean('removeFromSingleShift'),
                $shiftCountService,
                $changeService
            );

            if (!$shift->event_id) {
                broadcast(new RemoveEntityFormShiftEvent(
                    $shift->load([
                        'craft',
                        'users',
                        'freelancer',
                        'serviceProvider',
                        'committedBy'
                    ]),
                    $shift->room_id,
                    $usersPivotId,
                    $userType
                ));
            } else {
                broadcast(new RemoveEntityFormShiftEvent(
                    $shift->load([
                        'craft',
                        'users',
                        'freelancer',
                        'serviceProvider',
                        'committedBy'
                    ]),
                    $shift->event->room_id,
                    $usersPivotId,
                    $userType
                ));
            }

            return null;
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


        // create broadcast event for shift plan update

        if (!$shift->event_id) {
            broadcast(new RemoveEntityFormShiftEvent(
                $shift->load([
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'committedBy'
                ]),
                $shift->room_id,
                $usersPivotId,
                $userType
            ));
        } else {
            broadcast(new RemoveEntityFormShiftEvent(
                $shift->load([
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'committedBy'
                ]),
                $shift->event->room_id,
                $usersPivotId,
                $userType
            ));
        }


        return $isShiftTab ? $this->redirector->back() : null;
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

        return $this->redirector->back();
    }

    public function updateDescription(Request $request, Shift $shift): RedirectResponse
    {
        $shift->update($request->only(['description']));

        return $this->redirector->back();
    }

    public function updateUserCell(Request $request): void
    {
        $comment = $request->get('comment');
        $vacationType = $request->get('vacation_type');
        $entities = $request->get('entities');
        $individualTimes = $request->get('individual_times');

        foreach ($entities as $entity) {
            $modelClass = match ($entity['type']) {
                1 => Freelancer::class,
                2 => ServiceProvider::class,
                default => User::class,
            };

            $entityModel = $modelClass::findOrFail($entity['id']);

            // Process individual times and comments for each day
            foreach ($entity['days'] as $day) {
                foreach ($individualTimes as $time) {
                    $this->individualTimeService->createForModel(
                        $entityModel,
                        $time['title'],
                        $time['start_time'],
                        $time['end_time'],
                        $day
                    );
                }

                if (!empty($comment)) {
                    $this->shiftPlanCommentService->addOrUpdateShiftPlanCommentByModel(
                        $entityModel,
                        $comment,
                        $day
                    );
                }
            }

            // Bulk update vacations for all days at once to avoid N+1 queries
            if (!$entityModel instanceof ServiceProvider) {
                $this->vacationService->updateVacationsOfEntityBulk(
                    $vacationType,
                    $modelClass,
                    $entityModel,
                    $entity['days']
                );
            }
        }
    }

    public function deleteMultiEditCell(Request $request): void
    {
        $entities = $request->get('entities');
        $shifts = collect(); // Verwende eine Collection, um alle Shifts zu sammeln

        foreach ($entities as $entity) {
            $modelClass = match ($entity['type']) {
                1 => Freelancer::class,
                2 => ServiceProvider::class,
                default => User::class,
            };

            $entityModel = $modelClass::findOrFail($entity['id']);

            foreach ($entity['days'] as $day) {
                $this->individualTimeService->deleteForModel($entityModel, $day);

                $vacations = collect();
                if (!$entityModel instanceof ServiceProvider) {
                    $vacations = $entityModel->vacations()->where('date', $day)->get();
                }

                if ($vacations->isNotEmpty()) {
                    $this->vacationService->deleteVacationInterval($entityModel, $day);
                }

                $entityModel->shiftPlanComments()->where('date', $day)->delete();

                $dayShifts = $entityModel->shifts()->where('shifts.start_date', $day)->get();
                $this->shiftService->detachFromShifts($dayShifts, $modelClass, $entityModel);

                $shifts = $shifts->merge($dayShifts); // Merge neue Shifts mit den vorherigen
            }
        }

        if ($shifts->isNotEmpty()) {
            broadcast(new MultiShiftCreateInShiftPlan($shifts));
        }
    }


    public function updateTimeLine(Event $event, Request $request): void
    {
        $this->eventTimelineService->updateTimeLines($event, $request->get('dataset'));

        $freshEvent = $event->fresh();
        broadcast(new \Artwork\Modules\Event\Events\EventCreated($freshEvent, $freshEvent?->room_id));
    }

    public function addTimeLine(Event $event, Request $request): void
    {
        $this->eventTimelineService->addTimeLines($event, $request->get('dataset'));

        $freshEvent = $event->fresh();
        broadcast(new \Artwork\Modules\Event\Events\EventCreated($freshEvent, $freshEvent?->room_id));
    }

    public function importTimelinePreset(Event $event, ShiftPresetTimeline $shiftPresetTimeline): void
    {
        $this->eventTimelineService->importTimelinePreset($event, $shiftPresetTimeline);
    }

    public function storeTimelinePresetFormEvent(Event $event, Request $request): void
    {
        $this->eventTimelineService->storeTimelinePresetFromEvent($event, $request->get('name'));
    }

    public function storeShiftWithoutEvent(
        Request $request,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        $shift = $this->shiftService->createShiftWithoutEventAutomatic(
            craftId: $request->craft_id,
            data: $request->all(),
            day: $request->string('day'),
        );
        $shiftUuid = Str::uuid();
        $shift->shift_uuid = $shiftUuid;

        $this->shiftService->save($shift);

        $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);

        $shiftSave = $shift->fresh();

        foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
            $shiftsQualificationsService->createShiftsQualificationForShift($shift->id, $shiftsQualification);
        }
        /** @var Shift $shiftSave */
        broadcast(new CreatedShiftInShiftPlan($shiftSave, $shiftSave->room_id));
    }

    public function deleteCalendarCell(Request $request): void
    {

        $entities = $request->get('entities');
        foreach ($entities as $entity) {
            $room = Room::findOrFail($entity['roomId']);

            // find room shift by date
            $roomShifts = $room->shifts()
                ->where('shifts.start_date', Carbon::parse($entity['day'])->format('Y-m-d'))
                ->get();

            $roomShifts->each(function ($roomShift) use ($entity): void {
                $roomShift->users()->detach();
                $roomShift->freelancer()->detach();
                $roomShift->serviceProvider()->detach();
                $roomShift->shiftsQualifications()->delete();

                broadcast(new DestroyShift($roomShift, $roomShift->room_id));

                $roomShift->delete();
            });
        }
    }


    /**
     * Check for collisions in shifts for given people and time range.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCollisions(Request $request): \Illuminate\Http\JsonResponse
    {

        try {
            $people = $request->input('people', []);
            $shiftId = $request->input('shift_id');
            $startDateRaw = $request->input('start_date');
            $endDateRaw = $request->input('end_date');
            $startRaw = $request->input('start');
            $endRaw = $request->input('end');

            // Validate required parameters
            if (empty($startDateRaw) || empty($endDateRaw) || empty($startRaw) || empty($endRaw)) {
                return response()->json(['error' => 'Missing required parameters'], 400);
            }

            // Aktuelle Schicht Zeiten
            try {
                $currentStart = Carbon::parse(Carbon::parse($startDateRaw)->toDateString() . ' ' . $startRaw);
                $currentEnd = Carbon::parse(Carbon::parse($endDateRaw)->toDateString() . ' ' . $endRaw);
                if ($currentEnd <= $currentStart) {
                    $currentEnd->addDay();
                }
            } catch (\Exception $e) {
                Log::error('Error parsing date/time values', [
                    'start_date' => $startDateRaw,
                    'end_date' => $endDateRaw,
                    'start' => $startRaw,
                    'end' => $endRaw,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Invalid date/time format'], 400);
            }

            $results = [];
            foreach ($people as $person) {
                if (!isset($person['type']) || !isset($person['id'])) {
                    continue; // Skip invalid person entries
                }

                $type = $person['type'];
                $id = $person['id'];
                $hasCollision = false;
                $collisionShifts = [];


            // Pivot holen
                $query = match ($type) {
                    'user' => ShiftUser::where('user_id', $id),
                    'freelancer' => ShiftFreelancer::where('freelancer_id', $id),
                    'service_provider' => ShiftServiceProvider::where('service_provider_id', $id),
                    default => null
                };
                if (!$query) {
                    continue;
                }

                $query = $query
                ->with('shift.craft')
                ->get();

                foreach ($query as $pivot) {
                    if (!$shift = $pivot->shift) {
                        continue;
                    }
                    // Datum + Zeit - korrekt zusammensetzen
                    try {
                        // Validate that all required date/time values are available
                        $startDate = $pivot->start_date ?? $shift->start_date ?? null;
                        $startTime = $pivot->start_time ?? $shift->start ?? null;
                        $endDate = $pivot->end_date ?? $shift->end_date ?? null;
                        $endTime = $pivot->end_time ?? $shift->end ?? null;
                        // Check if any required values are missing
                        if (!$startDate || !$startTime || !$endDate || !$endTime) {
                            continue; // Skip this shift
                        }

                        $startDate = Carbon::parse($startDate)->toDateString();
                        $endDate = Carbon::parse($endDate)->toDateString();
                        $startTime = Carbon::parse($startTime)->format('H:i');
                        $endTime = Carbon::parse($endTime)->format('H:i');
                        $shiftStart = Carbon::parse($startDate . ' ' . $startTime);
                        $shiftEnd = Carbon::parse($endDate . ' ' . $endTime);
                    } catch (\Exception $e) {
                        continue; // Skip this shift
                    }
                    if ($shiftEnd <= $shiftStart) {
                        $shiftEnd->addDay();
                    }
                    // Kollisionslogik - Prüfung aller drei Kollisionsfälle
                    $case1 = $currentStart >= $shiftStart && $currentStart < $shiftEnd;
                    $case2 = $currentEnd > $shiftStart && $currentEnd <= $shiftEnd;
                    $case3 = $currentStart <= $shiftStart && $currentEnd >= $shiftEnd;

                    // Special case for exact time match (mentioned in issue description)
                    // This handles the case where a user is assigned to a shift with the exact same time as the current shift
                    $exactMatch = $currentStart->format('H:i') === $shiftStart->format('H:i') &&
                              $currentEnd->format('H:i') === $shiftEnd->format('H:i') &&
                              $currentStart->toDateString() === $shiftStart->toDateString() &&
                              $currentEnd->toDateString() === $shiftEnd->toDateString();
                    // Check for any collision case OR exact time match
                    if ($case1 || $case2 || $case3 || $exactMatch) {
                        $hasCollision = true;

                        // Für Debugging: Welcher Fall hat die Kollision ausgelöst
                        $collisionCase = '';
                        if ($case1) {
                            $collisionCase .= '1';
                        }
                        if ($case2) {
                            $collisionCase .= '2';
                        }
                        if ($case3) {
                            $collisionCase .= '3';
                        }
                        if ($exactMatch) {
                            $collisionCase .= 'E'; // E for Exact match
                        }
                        $collisionShifts[] = [
                        'id' => $shift->id,
                        'start' => $shiftStart->format('Y-m-d H:i'),
                        'end' => $shiftEnd->format('Y-m-d H:i'),
                        'description' => $shift->description,
                        'craftAbbreviation' => $shift->craft?->abbreviation ?? '',
                        'collisionCase' => $collisionCase,
                        'isExactMatch' => $exactMatch,
                        'shiftStartTime' => $shiftStart->format('H:i'),
                        'shiftEndTime' => $shiftEnd->format('H:i'),
                        'currentStartTime' => $currentStart->format('H:i'),
                        'currentEndTime' => $currentEnd->format('H:i')
                        ];
                    }
                }

            // Double-check that hasCollision is set correctly based on collisionShifts
                if (!$hasCollision && count($collisionShifts) > 0) {
                    // Fix the inconsistency by setting hasCollision to true if there are collision shifts
                    $hasCollision = true;
                }

                $results[] = [
                'id' => $id,
                'type' => $type,
                'hasCollision' => $hasCollision,
                'collisionShifts' => $collisionShifts,
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Unexpected error in checkCollisions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    public function storeShiftMultiAdd(
        Request $request,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        $roomsAndDatesForMultiEdit = $request->get('roomsAndDatesForMultiEdit');
        $createdShifts = collect();
        foreach ($roomsAndDatesForMultiEdit as $roomAndDate) {
            $data = [
                'start' => $request->get('start'),
                'end' => $request->get('end'),
                'break_minutes' => $request->get('break_minutes'),
                'description' => $request->get('description'),
                'room_id' => $roomAndDate['roomId'],
                'project_id' => $request->get('project_id'),
                'shift_group_id' => $request->get('shift_group_id'),
            ];

            $shift = $this->shiftService->createShiftWithoutEventAutomatic(
                craftId: $request->get('craft_id'),
                data: $data,
                day: Carbon::parse($roomAndDate['day'])->format('Y-m-d'),
            );

            $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);

            $shiftUuid = Str::uuid();
            $shift->shift_uuid = $shiftUuid;
            $this->shiftService->save($shift);
            $createdShifts->add($shift->fresh());
        }

        foreach ($createdShifts as $shiftSave) {
            foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
                $shiftsQualificationsService->createShiftsQualificationForShift($shiftSave->id, $shiftsQualification);
            }
        }
        broadcast(new MultiShiftCreateInShiftPlan($createdShifts));
    }

    public function updateIndividualShiftTime(Request $request)
    {
        $shiftId = $request->get('shiftPivotId');
        $entity = $request->get('entity');
        $startTime = $request->get('start_time');
        $endTime = $request->get('end_time');


        // Pivot holen
        $query = match ($entity['type']) {
            'user' => ShiftUser::find($shiftId),
            'freelancer' => ShiftFreelancer::find($shiftId),
            'service_provider' => ShiftServiceProvider::find($shiftId),
            default => null
        };
        if (!$query) {
            return response()->json(['error' => 'Shift pivot not found'], 404);
        }


        $startDate = Carbon::parse($query->start_date ?? $query->shift->start_date)->toDateString();
        $endDate = Carbon::parse($query->end_date ?? $query->shift->end_date)->toDateString();
        $startDateTime = Carbon::parse($startDate . ' ' . $startTime);
        $endDateTime = Carbon::parse($endDate . ' ' . $endTime);

        if ($endDateTime <= $startDateTime) {
            $endDateTime->addDay();
        }

        // Update the pivot with new start and end times
        $query->update([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_date' => $startDateTime->format('Y-m-d'),
            'end_date' => $endDateTime->format('Y-m-d'),
        ]);
    }

    public function updateShortDescription(Request $request): void
    {
        $shiftId = $request->get('shiftPivotId');
        $entity = $request->get('entity');

        // Pivot holen
        $query = match ($entity['type']) {
            'user' => ShiftUser::find($shiftId),
            'freelancer' => ShiftFreelancer::find($shiftId),
            'service_provider' => ShiftServiceProvider::find($shiftId),
            default => null
        };
        if (!$query) {
            return;
        }

        // Update the pivot with new short description
        $query->update([
            'short_description' => $request->get('short_description'),
        ]);

        // Broadcast the updated shift
        if (!$query->shift->event_id) {
            broadcast(new UpdateShiftInShiftPlan($query->shift, $query->shift->room_id));
        } else {
            broadcast(new UpdateShiftInShiftPlan($query->shift, $query->shift->event->room_id));
        }
    }

    public function updateWorkflowSettings(Request $request): RedirectResponse
    {
        $this->generalSettings->shift_commit_workflow_enabled = $request->input('shift_commit_workflow');
        $this->generalSettings->save();

        return back();
    }

}
