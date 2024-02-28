<?php

namespace Artwork\Modules\Vacation\Services;

use App\Enums\NotificationConstEnum;
use App\Models\Freelancer;
use App\Models\User;
use App\Support\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Repository\VacationConflictRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class VacationConflictService
{
    public function __construct(
        private readonly VacationConflictRepository $vacationConflictRepository,
        protected readonly NotificationService $notificationService,
    ) {
    }


    public function create(array $data): \Artwork\Core\Database\Models\Model
    {
        $conflict = new VacationConflict();
        $conflict->fill($data);
        return $this->vacationConflictRepository->save($conflict);
    }


    public function checkVacationConflictsOnDay(
        string $day,
        ?User $user = null,
        ?Freelancer $freelancer = null,
    ): void {
        $shifts = collect();
        $vacations = collect();
        if ($user) {
            $shifts = $user->shifts()->where('event_start_day', $day)->isCommitted()->get();
            $vacations = $user
                ->vacations()
                ->get();
        }

        if ($freelancer) {
            $shifts = $freelancer->shifts()->where('event_start_day', $day)->isCommitted()->get();
            $vacations = $freelancer
                ->vacations()
                ->get();
        }


        foreach ($shifts as $shift) {
            if ($user) {
                $shiftCommittedBy = $shift->committedBy()->first();
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
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setButtons(['see_shift']);
                $this->notificationService->setShiftId($shift->id);
            }
            if ($vacations->count() > 0) {
                foreach ($vacations as $vacation) {
                    $vacation->conflicts()->each(function ($conflict): void {
                        $conflict->delete();
                    });
                    // check if vacation is full_day
                    if ($vacation->full_day) {
                        $this->create([
                            'vacation_id' => $vacation->id,
                            'shift_id' => $shift->id,
                            'user_name' => $shiftCommittedBy->full_name,
                            'date' => $shift->event_start_day,
                            'start_time' => $shift->start,
                            'end_time' => $shift->end,
                        ]);
                        if ($user) {
                            $this->notificationService->setNotificationTo($user);
                            $this->notificationService->createNotification();
                        }
                    } else {
                        // check if shift is on vacation time
                        $start = Carbon::parse($vacation->start_time);
                        $end = Carbon::parse($vacation->end_time);
                        if (
                            $start->between($shift->start, $shift->end) ||
                            $end->between($shift->start, $shift->end)
                        ) {
                            $conflict = $this->create([
                                'vacation_id' => $vacation->id,
                                'shift_id' => $shift->id,
                                'user_name' => $shiftCommittedBy->full_name,
                                'date' => $shift->event_start_day,
                                'start_time' => $shift->start,
                                'end_time' => $shift->end,
                            ]);
                            if ($user) {
                                $this->notificationService->setNotificationTo($user);
                                $this->notificationService->createNotification();
                            }
                        }
                    }
                }
            }
        }
    }

    public function checkVacationConflictsShifts(
        Shift $shift,
        ?User $user = null,
        ?Freelancer $freelancer = null,
    ): void {

        $vacations = collect();
        if ($user) {
            $vacations = $user
                ->vacations()
                ->get();
        }

        if ($freelancer) {
            $vacations = $freelancer
                ->vacations()
                ->get();
        }

        if ($user) {
            $shiftCommittedBy = $shift->committedBy()->first();
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
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
            ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setButtons(['see_shift']);
            $this->notificationService->setShiftId($shift->id);
        }
        if ($vacations->count() > 0) {
            foreach ($vacations as $vacation) {
                $vacation->conflicts()->each(function ($conflict): void {
                    $conflict->delete();
                });
                // check if vacation is full_day
                if ($vacation->full_day) {
                    $this->create([
                        'vacation_id' => $vacation->id,
                        'shift_id' => $shift->id,
                        'user_name' => $shiftCommittedBy->full_name,
                        'date' => $shift->event_start_day,
                        'start_time' => $shift->start,
                        'end_time' => $shift->end,
                    ]);
                    if ($user) {
                        $this->notificationService->setNotificationTo($user);
                        $this->notificationService->createNotification();
                    }
                } else {
                    // check if shift is on vacation time
                    $start = Carbon::parse($vacation->start_time);
                    $end = Carbon::parse($vacation->end_time);
                    if (
                        $start->between($shift->start, $shift->end) ||
                        $end->between($shift->start, $shift->end)
                    ) {
                        $this->create([
                            'vacation_id' => $vacation->id,
                            'shift_id' => $shift->id,
                            'user_name' => $shiftCommittedBy->full_name,
                            'date' => $shift->event_start_day,
                            'start_time' => $shift->start,
                            'end_time' => $shift->end,
                        ]);
                        if ($user) {
                            $this->notificationService->setNotificationTo($user);
                            $this->notificationService->createNotification();
                        }
                    }
                }
            }
        }
    }
}
