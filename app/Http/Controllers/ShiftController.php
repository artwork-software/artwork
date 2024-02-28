<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Enums\RoleNameEnum;
use App\Models\Event;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class ShiftController extends Controller
{
    protected ?NewHistoryService $history = null;

    protected ?NotificationService $notificationService = null;

    public function __construct(
        private readonly AvailabilityConflictService $availabilityConflictService,
        private readonly VacationConflictService $vacationConflictService,
    ) {
        $this->history = new NewHistoryService('Artwork\Modules\Shift\Models\Shift');
        $this->notificationService = new NotificationService();
    }

    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(
        Request $request,
        Event $event,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        $shift = $event->shifts()->create($request->only([
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

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


            foreach ($seriesEvents as $seriesEvent) {
                if ($seriesEvent->id != $event->id) {
                    $newShift = $seriesEvent->shifts()->create($request->only([
                        'start',
                        'end',
                        'break_minutes',
                        'craft_id',
                        'number_employees',
                        'number_masters',
                        'description',
                    ]));
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
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);

            $this->notificationService->setButtons(['change_shift', 'delete_shift_notification']);
            $this->notificationService->setProjectId($shift->event()->first()->project()->first()->id);
            $this->notificationService->setEventId($shift->event()->first()->id);
            $this->notificationService->setShiftId($shift->id);
            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
                //$notificationTitle = 'Schicht mit zu kurzer Pausenzeit angelegt ';
                $notificationTitle = __('notification.shift.short_break', [], $authUser->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.keyWords.concerns') .
                            $shift->event()->first()->project()->first()->name . ' , ' .
                            $shift->craft()->first()->abbreviation . ' ' .
                            Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                            Carbon::parse($shift->end)->format('d.m.Y H:i'),
                        'href' => null
                    ],
                ];

                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($authUser);
                $this->notificationService->createNotification();
            }
        }

        $this->history
            ->createHistory($shift->id, 'Schicht von Event ' . $event->eventName . ' wurde erstellt', 'shift');
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
            $this->history
                ->createHistory($shift->id, 'Schicht von Event ' . $event->eventName . ' wurde bearbeitet', 'shift');
        }
        $shift->update($request->only([
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

        return Redirect::route('shifts.plan')->with('success', 'Shift updated');
    }

    public function updateShift(
        Request $request,
        Shift $shift,
        ShiftsQualificationsService $shiftsQualificationsService
    ): RedirectResponse {
        $projectId =  $shift->event()->first()->project()->first()->id;
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history
                ->createHistory($shift->id, 'Schicht von Event ' . $event->eventName . ' wurde bearbeitet', 'shift');

            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);

            foreach ($shift->users()->get() as $user) {
                //$notificationTitle = 'Schichtänderung trotz Festschreibung ' .
                //    $shift->event()->first()->project()->first()->name . ' ' . $shift->craft()->first()->abbreviation;
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

        return Redirect::route('projects.show.shift', $projectId)->with('success', 'Shift updated');
    }

    private function sendShiftAddedNotificationToUser(Shift $shift, User $user): void
    {
        /*$notificationTitle = 'Neue Schichtbesetzung ' . $shift->event()->first()->project()
                ->first()->name . ' ' . $shift->craft()->first()->abbreviation;*/
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
            ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();
    }

    private function setConflictNotificationHeaderAndData(Shift $shift, $shiftCommittedBy): void
    {


        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService
            ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT);
        $this->notificationService->setButtons(['see_shift']);
        $this->notificationService->setShiftId($shift->id);
    }

    public function updateCommitments(Request $request): RedirectResponse
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

                    $this->setConflictNotificationHeaderAndData($shift, $shiftCommittedBy);

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

        return Redirect::route('projects.show.shift', $projectId)->with('success', 'Shift updated');
    }

    public function destroy(Shift $shift): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history
                ->createHistory($shift->id, 'Schicht von Event ' . $event->eventName . ' wurde gelöscht', 'shift');


            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);


            foreach ($shift->users()->get() as $user) {
                if (Auth::id() !== $user->id) {
                    /*$notificationTitle = 'Schicht gelöscht trotz Festschreibung ' .
                        $shift->event()->first()->project()->first()->name . ' ' . $shift->craft()->first()->abbreviation;*/
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
                    /*$notificationTitle = 'Schicht gelöscht trotz Festschreibung ' .
                        $shift->event()->first()->project()->first()->name . ' ' . $shift->craft()->first()->abbreviation;*/
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

        $shift->delete();
    }

    public function saveMultiEdit(
        Request $request,
        ShiftService $shiftService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
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
            $serviceToUse->removeFromShiftByUserIdAndShiftId(
                $request->get('userTypeId'),
                $shiftIdToRemove
            );
        }

        foreach ($shiftsToHandle['assignToShift'] as $shiftToAssign) {
            $shift = $shiftService->getById($shiftToAssign['shiftId']);

            if (!$shift instanceof Shift) {
                continue;
            }

            $serviceToUse->assignToShift(
                $shift,
                $request->get('userTypeId'),
                $shiftToAssign['shiftQualificationId'],
            );
        }
    }

    public function assignToShift(
        Shift $shift,
        Request $request,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
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

        $serviceToUse->assignToShift(
            $shift,
            $request->get('userId'),
            $request->get('shiftQualificationId'),
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
        ShiftServiceProviderService $shiftServiceProviderService
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

        $serviceToUse->removeFromShift(
            $usersPivotId,
            $request->boolean('removeFromSingleShift')
        );

        return Redirect::back();
    }

    public function removeAllShiftUsers(
        Shift $shift,
        ShiftService $shiftService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): RedirectResponse {
        $shiftUserService->removeAllUsersFromShift($shift);
        $shiftFreelancerService->removeAllFreelancersFromShift($shift);
        $shiftServiceProviderService->removeAllServiceProvidersFromShift($shift);

        if ($shift->is_committed) {
            $shiftService->createRemovedAllUsersFromShiftHistoryEntry($shift);
        }

        return Redirect::back();
    }
}
