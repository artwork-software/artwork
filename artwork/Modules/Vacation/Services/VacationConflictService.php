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

readonly class VacationConflictService
{
    public function __construct(private VacationConflictRepository $vacationConflictRepository)
    {
    }

    public function create(array $data): VacationConflict
    {
        $conflict = new VacationConflict();
        $conflict->fill($data);

        $this->vacationConflictRepository->save($conflict);

        return $conflict;
    }

    //@todo: fix phpcs error - fix complexity and nesting level
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function checkVacationConflictsOnDay(
        string $day,
        ?User $user,
        ?Freelancer $freelancer,
        NotificationService $notificationService
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

                $notificationService->setTitle($notificationTitle);
                $notificationService->setIcon('red');
                $notificationService->setPriority(2);
                $notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT);
                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setDescription($notificationDescription);
                $notificationService->setButtons(['see_shift']);
                $notificationService->setShiftId($shift->id);
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
                            $notificationService->setNotificationTo($user);
                            $notificationService->createNotification();
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
                                $notificationService->setNotificationTo($user);
                                $notificationService->createNotification();
                            }
                        }
                    }
                }
            }
        }
    }

    public function checkVacationConflictsShifts(
        Shift $shift,
        NotificationService $notificationService,
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

            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('red');
            $notificationService->setPriority(2);
            $notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_CONFLICT);
            $notificationService->setBroadcastMessage($broadcastMessage);
            $notificationService->setDescription($notificationDescription);
            $notificationService->setButtons(['see_shift']);
            $notificationService->setShiftId($shift->id);
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
                        $notificationService->setNotificationTo($user);
                        $notificationService->createNotification();
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
                            $notificationService->setNotificationTo($user);
                            $notificationService->createNotification();
                        }
                    }
                }
            }
        }
    }
}
