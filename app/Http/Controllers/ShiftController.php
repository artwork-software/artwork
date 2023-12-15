<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Enums\RoleNameEnum;
use App\Models\Event;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\Shift;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class ShiftController extends Controller
{
    protected ?NewHistoryService $history = null;

    protected ?NotificationService $notificationService = null;

    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\Shift');
        $this->notificationService = new NotificationService();
    }

    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request, Event $event): void
    {
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
                }
            }
        }

        if ($event->is_series) {
            $shift->update([
                'shift_uuid' => $shiftUuid,
            ]);
        }


        if ($shift->infringement) {
            $notificationTitle = 'Schicht mit zu kurzer Pausenzeit angelegt ';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $shift->event()->first()->project()->first()->name . ' , ' .
                        $shift->craft()->first()->abbreviation . ' ' .
                        Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                        Carbon::parse($shift->end)->format('d.m.Y H:i'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setButtons(['change_shift', 'delete_shift_notification']);
            $this->notificationService->setProjectId($shift->event()->first()->project()->first()->id);
            $this->notificationService->setEventId($shift->event()->first()->id);
            $this->notificationService->setShiftId($shift->id);
            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
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

    public function updateShift(Request $request, Shift $shift): RedirectResponse
    {
        $projectId =  $shift->event()->first()->project()->first()->id;
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history
                ->createHistory($shift->id, 'Schicht von Event ' . $event->eventName . ' wurde bearbeitet', 'shift');
            $notificationTitle = 'Schichtänderung trotz Festschreibung ' .
                $shift->event()->first()->project()->first()->name . ' ' . $shift->craft()->first()->abbreviation;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft Schicht: ' . Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                        Carbon::parse($shift->end)->format('d.m.Y H:i'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            foreach ($shift->users()->get() as $user) {
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }

            $craft = $shift->craft()->first();

            foreach ($craft->users()->get() as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
        } else {
            $notificationTitle = 'Schichtänderung ' . $shift->event()->first()->project()->first()->name . ' ' .
                $shift->craft()->first()->abbreviation;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft Schicht: ' . Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                        Carbon::parse($shift->end)->format('d.m.Y H:i'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);

            foreach ($shift->users()->get() as $user) {
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }

            $craft = $shift->craft()->first();

            foreach ($craft->users()->get() as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
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

        return Redirect::route('projects.show.shift', $projectId)->with('success', 'Shift updated');
    }

    public function updateCommitments(Request $request): RedirectResponse
    {
        $projectId = $request->input('project_id');
        $shiftIds = $request->input('shifts');
        $updateData = $request->only([
            'is_committed',
        ]);

        Shift::whereIn('id', $shiftIds)->update($updateData);
        return Redirect::route('projects.show.shift', $projectId)->with('success', 'Shift updated');
    }

    public function destroy(Shift $shift): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history
                ->createHistory($shift->id, 'Schicht von Event ' . $event->eventName . ' wurde gelöscht', 'shift');

            $notificationTitle = 'Schicht gelöscht trotz Festschreibung ' .
                $shift->event()->first()->project()->first()->name . ' ' . $shift->craft()->first()->abbreviation;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft Schicht: ' . Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                        Carbon::parse($shift->end)->format('d.m.Y H:i'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);

            foreach ($shift->users()->get() as $user) {
                if (Auth::id() !== $user->id) {
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                }
            }

            $craft = $shift->craft()->first();

            foreach ($craft->users()->get() as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
        } else {
            $notificationTitle = 'Schicht gelöscht  ' . $shift->event()->first()->project()->first()->name . ' ' .
                $shift->craft()->first()->abbreviation;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft Schicht: ' . Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                        Carbon::parse($shift->end)->format('d.m.Y H:i'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);

            foreach ($shift->users()->get() as $user) {
                if (Auth::id() !== $user->id) {
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                }
            }

            $craft = $shift->craft()->first();

            foreach ($craft->users()->get() as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
        }

        $shift->delete();
    }

    protected function calculateShiftCollision(
        Shift $shift,
        array $eventIds,
        int $user_id = null,
        int $freelancer_id = null,
        int $service_provider_id = null
    ): Collection {
        $shift->load('event');

        /** @var Event $event */
        $event = $shift->event;
        $startDate = $event->start_time;
        $endDate = $event->end_time;

        return Event::query()
            ->whereIntegerInRaw('id', $eventIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orWhere(function ($query) use ($endDate, $startDate, $eventIds): void {
                $query->whereBetween('end_time', [$startDate, $endDate])
                    ->whereIntegerInRaw('id', $eventIds);
            })
            ->orWhere(function ($query) use ($endDate, $startDate, $eventIds): void {
                $query->where('start_time', '>=', $startDate)
                    ->where('end_time', '<=', $endDate)
                    ->whereIntegerInRaw('id', $eventIds);
            })
            ->orWhere(function ($query) use ($endDate, $startDate, $eventIds): void {
                $query->where('start_time', '<=', $startDate)
                    ->where('end_time', '>=', $endDate)
                    ->whereIntegerInRaw('id', $eventIds);
            })
            ->with('shifts')
            ->get()
            ->pluck('shifts')
            ->flatten()
            ->reject(
                fn(Shift $currentShift) =>
                    // remove the shift I am attaching to
                    $currentShift->id === $shift->id
                    // remove shifts that start after the shift I am attaching to has ended
                    || $currentShift->start > $shift->end
                    // remove shifts ended before the shift I am attaching to has started
                    || $currentShift->end < $shift->start
                    || $user_id && $currentShift->users->doesntContain($user_id)
                    || $freelancer_id && $currentShift->freelancer->doesntContain($freelancer_id)
                    || $service_provider_id && $currentShift->service_provider->doesntContain($service_provider_id)
            )
            ->pluck('id');
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh
    public function addShiftUser(Shift $shift, User $user, Request $request): void
    {
        $this->notificationService->setProjectId($shift->event()->first()->project()->first()->id);
        $this->notificationService->setEventId($shift->event()->first()->id);
        $this->notificationService->setShiftId($shift->id);

        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde zur Schicht (' .
                    $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') hinzugefügt',
                'shift'
            );
        }

        $eventIdsOfUserShifts = $user->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, user_id: $user->id);
        $collideCount = $collidingShiftIds->count();

        if ($collideCount > 0) {
            $user->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        //add user to the project team of the project of the event of the shift,
        //if the user is not already in the project team
        $event = $shift->event;
        $project = $event->project;
        if (!$project->users->contains($user->id)) {
            $project->users()->attach($user->id);
        }

        if (isset($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $start = Carbon::parse($request->chooseData['start'])->startOfDay();
                $end = Carbon::parse($request->chooseData['end'])->endOfDay();
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->where(function ($query) use ($start, $end): void {
                        $query->whereBetween('event_start_day', [$start, $end])
                            ->orWhereBetween('event_end_day', [$start, $end]);
                    })
                    ->get();
                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        if ($request->chooseData['dayOfWeek'] !== 'all') {
                            if (
                                Carbon::parse($allShift->event_start_day)->dayOfWeek !==
                                $request->chooseData['dayOfWeek']
                            ) {
                                continue;
                            }
                        }
                        if ($allShift->getEmptyUserCountAttribute() > 0) {
                            if (!$allShift->users->contains($user->id)) {
                                $allShift->users()->attach($user->id, ['shift_count' => $collideCount + 1]);
                            }
                        }
                    }
                }
            }
        }

        $shift->users()->attach($user->id, ['shift_count' => $collideCount + 1]);

        // if shift longer when 10h send notification
        $vacations = $user
            ->vacations()
            ->where('from', '<=', $shift->event_start_day)
            ->where('until', '>=', $shift->event_end_day)
            ->get();

        if ($vacations->count() > 0) {
            $notificationTitle = 'Schichtkonflikt ' . Carbon::parse($shift->event_start_day)->format('d.m.Y') .
                ' ' . $project->name . ' ' . $shift->craft()->first()->abbreviation;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $user->getFullNameAttribute() . ' ist nicht verfügbar',
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setButtons(['change_shift_conflict']);
            $crafts = $user->crafts()->get();
            $hasGetNotification = [];
            foreach ($crafts as $craft) {
                foreach ($craft->users()->get() as $craftUser) {
                    if (in_array($craftUser->id, $hasGetNotification)) {
                        continue;
                    }
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                    $hasGetNotification[] = $craftUser->id;
                }
            }
            $this->notificationService->clearNotificationData();
        }

        $notificationTitle = 'Neue Schichtbesetzung ' . $project->name . ' ' . $shift->craft()->first()->abbreviation;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => 'Deine Schicht: ' . Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
            ],
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();

        $shiftCheck = $this->notificationService->checkIfUserInMoreThanTeenShifts($user, $shift);
        $shiftBreakCheck = $this->notificationService->checkIfShortBreakBetweenTwoShifts($user, $shift);

        if ($shiftBreakCheck->shortBreak) {
            $notificationTitle = 'Du wurdest mit zu kurzer Ruhepause geplant';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y') . ' - ' .
                        Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();

            // send same notification to admin
            $notificationTitle = 'Mitarbeiter*in mit zu kurzer Ruhepause geplant';
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $this->notificationService->setButtons(['see_shift', 'delete_shift_notification']);

            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
                $this->notificationService->setNotificationTo($authUser);
                $this->notificationService->createNotification();
            }

            $crafts = $user->crafts()->get();
            $hasGetNotification = [];
            foreach ($crafts as $craft) {
                foreach ($craft->users()->get() as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    if (in_array($craftUser->id, $hasGetNotification)) {
                        continue;
                    }
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                    $hasGetNotification[] = $craftUser->id;
                }
            }
            $this->notificationService->clearNotificationData();
        }

        if ($shiftCheck->moreThanTenShifts) {
            $notificationTitle = 'Du wurdest mehr als 10 Tage am Stück eingeplant';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftCheck->firstShift->first()->event_start_day)->format('d.m.Y') .
                        ' - ' . Carbon::parse($shiftCheck->lastShift->first()->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();

            // send same notification to admin

            $notificationTitle = 'Mitarbeiter*in mehr als 10 Tage am Stück eingeplant';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftCheck->firstShift->first()->event_start_day)->format('d.m.Y') .
                        ' - ' . Carbon::parse($shiftCheck->lastShift->first()->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setButtons(['see_shift', 'delete_shift_notification']);

            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
                $this->notificationService->setNotificationTo($authUser);
                $this->notificationService->createNotification();
            }

            $crafts = $user->crafts()->get();
            foreach ($crafts as $craft) {
                foreach ($craft->users()->get() as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
            $this->notificationService->clearNotificationData();
        }
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh
    public function addShiftMaster(Request $request, Shift $shift, User $user): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde  zur Schicht (' .
                    $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') als Meister hinzugefügt',
                'shift'
            );
        }

        $eventIdsOfUserShifts = $user->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, user_id: $user->id);

        $collideCount = $collidingShiftIds->count();

        if ($collideCount > 0) {
            $user->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        //add user to the project team of the project of the event of the shift,
        //if the user is not already in the project team
        $event = $shift->event;
        $project = $event->project;
        if (!$project->users->contains($user->id)) {
            $project->users()->attach($user->id);
        }

        if (isset($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $start = Carbon::parse($request->chooseData['start'])->startOfDay();
                $end = Carbon::parse($request->chooseData['end'])->endOfDay();
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->where(function ($query) use ($start, $end): void {
                        $query->whereBetween('event_start_day', [$start, $end])
                            ->orWhereBetween('event_end_day', [$start, $end]);
                    })
                    ->get();
                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        if ($request->chooseData['dayOfWeek'] !== 'all') {
                            if (
                                Carbon::parse($allShift->event_start_day)->dayOfWeek !==
                                $request->chooseData['dayOfWeek']
                            ) {
                                continue;
                            }
                        }
                        if ($allShift->getEmptyMasterCountAttribute() > 0) {
                            if (!$allShift->users->contains($user->id)) {
                                $allShift->users()->attach(
                                    $user->id,
                                    ['is_master' => true, 'shift_count' => $collideCount + 1]
                                );
                            }
                        }
                    }
                }
            }
        }

        $notificationTitle = 'Neue Schichtbesetzung ' . $project->name . ' ' . $shift->craft()->first()->abbreviation;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => 'Deine Schicht: ' .
                    Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
            ],
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();


        $shiftCheck = $this->notificationService->checkIfUserInMoreThanTeenShifts($user, $shift);
        $shiftBreakCheck = $this->notificationService->checkIfShortBreakBetweenTwoShifts($user, $shift);

        if ($shiftBreakCheck->shortBreak) {
            $notificationTitle = 'Du wurdest mit zu kurzer Ruhepause geplant';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftBreakCheck->firstShift->event_start_day)->format('d.m.Y') . ' - ' .
                        Carbon::parse($shiftBreakCheck->lastShift->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();

            // send same notification to admin
            $notificationTitle = 'Mitarbeiter*in mit zu kurzer Ruhepause geplant';
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $this->notificationService->setButtons(['see_shift', 'delete_shift_notification']);

            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
                $this->notificationService->setNotificationTo($authUser);
                $this->notificationService->createNotification();
            }

            $crafts = $user->crafts()->get();
            foreach ($crafts as $craft) {
                foreach ($craft->users()->get() as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
        }

        if ($shiftCheck->moreThanTenShifts) {
            $notificationTitle = 'Du wurdest mehr als 10 Tage am Stück eingeplant';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftCheck->firstShift->event_start_day)->format('d.m.Y') . ' - ' .
                        Carbon::parse($shiftCheck->lastShift->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();

            // send same notification to admin
            $notificationTitle = 'Mitarbeiter*in mehr als 10 Tage am Stück eingeplant';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => 'Betrifft: ' . $user->getFullNameAttribute(),
                    'href' => null
                ],
                2 => [
                    'type' => 'string',
                    'title' => 'Zeitraum: ' .
                        Carbon::parse($shiftCheck->firstShift->event_start_day)->format('d.m.Y') . ' - ' .
                        Carbon::parse($shiftCheck->lastShift->event_start_day)->format('d.m.Y'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_INFRINGEMENT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setButtons(['change_shift', 'delete_shift_notification']);

            foreach (User::role(RoleNameEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
                $this->notificationService->setNotificationTo($authUser);
                $this->notificationService->createNotification();
            }

            $crafts = $user->crafts()->get();
            foreach ($crafts as $craft) {
                foreach ($craft->users()->get() as $craftUser) {
                    if ($craftUser->id === $user->id) {
                        continue;
                    }
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
        }

        $shift->users()->attach($user->id, ['is_master' => true, 'shift_count' => $collideCount + 1]);
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function addShiftFreelancer(Shift $shift, Freelancer $freelancer, Request $request): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Freelancer ' . $freelancer->getNameAttribute() . ' wurde zur Schicht (' .
                    $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') hinzugefügt',
                'shift'
            );
        }
        $eventIdsOfUserShifts = $freelancer->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this
            ->calculateShiftCollision($shift, $eventIdsOfUserShifts, freelancer_id: $freelancer->id);
        $collideCount = $collidingShiftIds->count();

        if ($collideCount > 0) {
            $freelancer->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        if (isset($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $start = Carbon::parse($request->chooseData['start'])->startOfDay();
                $end = Carbon::parse($request->chooseData['end'])->endOfDay();
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->where(function ($query) use ($start, $end): void {
                        $query->whereBetween('event_start_day', [$start, $end])
                            ->orWhereBetween('event_end_day', [$start, $end]);
                    })
                    ->get();

                foreach ($allShifts as $allShift) {
                    if ($request->chooseData['dayOfWeek'] !== 'all') {
                        if (
                            Carbon::parse($allShift->event_start_day)->dayOfWeek !==
                            $request->chooseData['dayOfWeek']
                        ) {
                            continue;
                        }
                    }
                    if ($allShift->id !== $shift->id) {
                        if ($allShift->getEmptyUserCountAttribute() > 0) {
                            if (!$allShift->freelancer->contains($freelancer->id)) {
                                $allShift->freelancer()->attach($freelancer->id, ['shift_count' => $collideCount + 1]);
                            }
                        }
                    }
                }
            }
        }

        $shift->freelancer()->attach($freelancer->id, ['shift_count' => $collideCount + 1]);
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function addShiftFreelancerMaster(Request $request, Shift $shift, Freelancer $freelancer): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Freelancer ' . $freelancer->getNameAttribute() . ' wurde zur Schicht (' .
                    $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') als Meister hinzugefügt',
                'shift'
            );
        }

        $eventIdsOfUserShifts = $freelancer->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this
            ->calculateShiftCollision($shift, $eventIdsOfUserShifts, freelancer_id: $freelancer->id);
        $collideCount = $collidingShiftIds->count();

        if ($collideCount > 0) {
            $freelancer->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        if (isset($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $start = Carbon::parse($request->chooseData['start'])->startOfDay();
                $end = Carbon::parse($request->chooseData['end'])->endOfDay();
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->where(function ($query) use ($start, $end): void {
                        $query->whereBetween('event_start_day', [$start, $end])
                            ->orWhereBetween('event_end_day', [$start, $end]);
                    })
                    ->get();
                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        if ($request->chooseData['dayOfWeek'] !== 'all') {
                            if (
                                Carbon::parse($allShift->event_start_day)->dayOfWeek !==
                                $request->chooseData['dayOfWeek']
                            ) {
                                continue;
                            }
                        }
                        if ($allShift->getEmptyMasterCountAttribute() > 0) {
                            if (!$allShift->freelancer->contains($freelancer->id)) {
                                $allShift->freelancer()->attach(
                                    $freelancer->id,
                                    ['is_master' => true, 'shift_count' => $collideCount + 1]
                                );
                            }
                        }
                    }
                }
            }
        }
        $shift->freelancer()->attach($freelancer->id, ['is_master' => true, 'shift_count' => $collideCount + 1]);
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function addShiftProviderMaster(Request $request, Shift $shift, ServiceProvider $serviceProvider): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde zur Schicht (' .
                    $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') als Meister hinzugefügt',
                'shift'
            );
        }

        $eventIdsOfUserShifts = $serviceProvider->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this
            ->calculateShiftCollision($shift, $eventIdsOfUserShifts, service_provider_id: $serviceProvider->id);
        $collideCount = $collidingShiftIds->count();

        if ($collideCount > 0) {
            $serviceProvider->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        if (isset($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $start = Carbon::parse($request->chooseData['start'])->startOfDay();
                $end = Carbon::parse($request->chooseData['end'])->endOfDay();
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->where(function ($query) use ($start, $end): void {
                        $query->whereBetween('event_start_day', [$start, $end])
                            ->orWhereBetween('event_end_day', [$start, $end]);
                    })
                    ->get();
                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        if ($request->chooseData['dayOfWeek'] !== 'all') {
                            if (
                                Carbon::parse($allShift->event_start_day)->dayOfWeek !==
                                $request->chooseData['dayOfWeek']
                            ) {
                                continue;
                            }
                        }
                        if ($allShift->getEmptyMasterCountAttribute() > 0) {
                            if (!$allShift->service_provider->contains($serviceProvider->id)) {
                                $allShift->service_provider()->attach(
                                    $serviceProvider->id,
                                    ['is_master' => true, 'shift_count' => $collideCount + 1]
                                );
                            }
                        }
                    }
                }
            }
        }
        $shift->service_provider()->attach(
            $serviceProvider->id,
            ['is_master' => true, 'shift_count' => $collideCount + 1]
        );
    }

    /**
     * @param Shift $shift
     * @param User $user
     * @return void
     */
    public function removeUser(Shift $shift, User $user, Request $request): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Mitarbeiter ' . $user->getFullNameAttribute() . ' wurde von Schicht (' .
                    $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') entfernt',
                'shift'
            );
        }
        $shift->users()->detach($user->id);

        $eventIdsOfUserShifts = $user->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this->calculateShiftCollision($shift, $eventIdsOfUserShifts, user_id: $user->id);
        $collideCount = $collidingShiftIds->count();


        if ($request->chooseData !== null) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->get();


                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        $allShift->users()->detach($user->id);
                    }
                }
            }
        }

        $user->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_count' => $collideCount > 0 ? $collideCount : 1
            ]
        );

        $notificationTitle = 'Schichtbesetzung gelöscht  ' . $shift->event()->first()->project()->first()->name . ' ' .
            $shift->craft()->first()->abbreviation;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => 'Betrifft Schicht: ' .
                    Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                    Carbon::parse($shift->end)->format('d.m.Y H:i'),
                'href' => null
            ],
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CHANGED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }

    public function removeFreelancer(Shift $shift, Freelancer $freelancer, Request $request): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Freelancer ' . $freelancer->getNameAttribute() . ' wurde von Schicht (' .
                $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') entfernt',
                'shift'
            );
        }
        $shift->freelancer()->detach($freelancer->id);

        if (!empty($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                   ->get();


                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        $allShift->freelancer()->detach($freelancer->id);
                    }
                }
            }
        }

        $eventIdsOfUserShifts = $freelancer->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this
            ->calculateShiftCollision($shift, $eventIdsOfUserShifts, freelancer_id: $freelancer->id);
        $collideCount = $collidingShiftIds->count();

        $freelancer->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_count' => $collideCount > 0 ? $collideCount : 1
            ]
        );
    }

    public function removeProvider(Shift $shift, ServiceProvider $serviceProvider, Request $request): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde von Schicht (' .
                    $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') entfernt',
                'shift'
            );
        }
        $shift->service_provider()->detach($serviceProvider->id);

        if (!empty($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->get();

                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        $allShift->service_provider()->detach($serviceProvider->id);
                    }
                }
            }
        }

        $eventIdsOfUserShifts = $serviceProvider->shifts()->get()->pluck('event.id')->all();

        $collidingShiftIds = $this
            ->calculateShiftCollision($shift, $eventIdsOfUserShifts, service_provider_id: $serviceProvider->id);
        $collideCount = $collidingShiftIds->count();

        $serviceProvider->shifts()->updateExistingPivot(
            $collidingShiftIds,
            [
                'shift_count' => $collideCount > 0 ? $collideCount : 1
            ]
        );
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function addShiftProvider(Shift $shift, ServiceProvider $serviceProvider, Request $request): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Dienstleister ' . $serviceProvider->getNameAttribute() . ' wurde zur Schicht (' .
                $shift->craft()->first()->abbreviation . ' - ' . $event->eventName . ') hinzugefügt',
                'shift'
            );
        }
        $eventIdsOfUserShifts = $serviceProvider->shifts()->get()->pluck('event.id')->all();
        $collidingShiftIds = $this
            ->calculateShiftCollision($shift, $eventIdsOfUserShifts, service_provider_id: $serviceProvider->id);
        $collideCount = $collidingShiftIds->count();

        if ($collideCount > 0) {
            $serviceProvider->shifts()->updateExistingPivot(
                $collidingShiftIds,
                ['shift_count' => $collideCount + 1]
            );
        }

        if (isset($request->chooseData)) {
            if ($request->chooseData['onlyThisDay'] === false) {
                $start = Carbon::parse($request->chooseData['start'])->startOfDay();
                $end = Carbon::parse($request->chooseData['end'])->endOfDay();
                $allShifts = Shift::where('shift_uuid', $shift->shift_uuid)
                    ->where(function ($query) use ($start, $end): void {
                        $query->whereBetween('event_start_day', [$start, $end])
                            ->orWhereBetween('event_end_day', [$start, $end]);
                    })
                    ->get();
                foreach ($allShifts as $allShift) {
                    if ($allShift->id !== $shift->id) {
                        if ($request->chooseData['dayOfWeek'] !== 'all') {
                            if (
                                Carbon::parse($allShift->event_start_day)->dayOfWeek !==
                                $request->chooseData['dayOfWeek']
                            ) {
                                continue;
                            }
                        }
                        if ($allShift->getEmptyUserCountAttribute() > 0) {
                            if (!$allShift->service_provider->contains($serviceProvider->id)) {
                                $allShift->service_provider()->attach(
                                    $serviceProvider->id,
                                    ['shift_count' => $collideCount + 1]
                                );
                            }
                        }
                    }
                }
            }
        }

        $shift->service_provider()->attach($serviceProvider->id, ['shift_count' => $collideCount + 1]);
    }

    public function clearEmployeesAndMaster(Shift $shift, Request $request): void
    {
        $users = $shift->users()->get();
        foreach ($users as $user) {
            $this->removeUser($shift, $user, $request);
        }
        $freelancers = $shift->freelancer()->get();
        foreach ($freelancers as $freelancer) {
            $this->removeFreelancer($shift, $freelancer, $request);
        }
        $serviceProviders = $shift->service_provider()->get();
        foreach ($serviceProviders as $serviceProvider) {
            $this->removeProvider($shift, $serviceProvider, $request);
        }
        if ($shift->is_committed) {
            $event = $shift->event;
            $this->history->createHistory(
                $shift->id,
                'Alle eingeplanten Mitarbeiter wurde von Schicht (' . $shift->craft()->first()->abbreviation .
                    ' - ' . $event->eventName . ') entfernt',
                'shift'
            );
        }
    }

    public function saveMultiEdit(Request $request): void
    {
        $shifts = $request->shifts;
        $user = null;
        $freelancer = null;
        $serviceProvider = null;
        $modelShifts = null;

        if ($request->user['type'] === 0) {
            $user = User::find($request->user['id']);
            $modelShifts = $user->shifts()->get()->pluck('id')->all();
            // remove all shifts from user
            $user->shifts()->detach($modelShifts);
        }

        if ($request->user['type'] === 1) {
            $freelancer = Freelancer::find($request->user['id']);
            $modelShifts = $freelancer->shifts()->get()->pluck('id')->all();
            // remove all shifts from freelancer
            $freelancer->shifts()->detach($modelShifts);
        }

        if ($request->user['type'] === 2) {
            $serviceProvider = ServiceProvider::find($request->user['id']);
            $modelShifts = $serviceProvider->shifts()->get()->pluck('id')->all();
            // remove all shifts from service provider
            $serviceProvider->shifts()->detach($modelShifts);
        }

        //dd($request->user['type']);
        foreach ($shifts as $shift => $key) {
            $shift = Shift::find($key);
            if ($request->user['type'] === 0) {
                $this->addShiftUser(shift: $shift, user: $user, request: $request);
            }
            if ($request->user['type'] === 1) {
                $this->addShiftFreelancer(shift: $shift, freelancer: $freelancer, request: $request);
            }

            if ($request->user['type'] === 2) {
                $this->addShiftProvider(shift: $shift, serviceProvider: $serviceProvider, request: $request);
            }
        }
    }
}
