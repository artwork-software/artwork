<?php

namespace Artwork\Modules\Vacation\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Services\ShiftNotificationLinkService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Support\ShiftSchedulerResolver;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Repository\VacationConflictRepository;
use Carbon\Carbon;
use Illuminate\Support\Str;

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

    /**
     * Zuweisende Person inkl. Quelle und Zeitpunkt. Bei Alt-Zuweisungen ohne
     * assigned_by_user_id liefert der Resolver den Festschreibenden mit der
     * Quelle "committed" — der Hinweis darf dann nicht behaupten, diese Person
     * habe eingeplant (im Schichtverlauf steht davon nichts).
     *
     * @return array{name: ?string, source: ?string, at: ?string}
     */
    private function resolveScheduler(Shift $shift, User|Freelancer|null $worker): array
    {
        return ShiftSchedulerResolver::resolve($shift, $worker);
    }


    /**
     * Konflikt-Text passend zur Quelle: nur bei einer echt erfassten Zuweisung
     * darf der Text sagen, die genannte Person habe eingeteilt.
     *
     * @param array{name: ?string, source: ?string, at: ?string} $scheduler
     */
    private function conflictNotificationTitle(
        array $scheduler,
        string $date,
        string $from,
        string $to,
        string $language
    ): string {
        $key = match (true) {
            $scheduler['source'] === ShiftSchedulerResolver::SOURCE_ASSIGNED => 'notification.shift.conflict_text',
            $scheduler['name'] !== null => 'notification.shift.conflict_text_committed',
            default => 'notification.shift.conflict_text_unknown',
        };

        return __($key, [
            'username' => $scheduler['name'],
            'date' => $date,
            'from' => $from,
            'to' => $to,
        ], $language);
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
                ->where('date', $day)
                ->get();
        }

        if ($freelancer) {
            $shifts = $freelancer->shifts()->where('event_start_day', $day)->isCommitted()->get();
            $vacations = $freelancer
                ->vacations()
                ->where('date', $day)
                ->get();
        }

        foreach ($shifts as $shift) {
            $scheduler = $this->resolveScheduler($shift, $user ?? $freelancer);
            $schedulerName = $scheduler['name'];
            if ($user) {
                $notificationTitle = __(
                    'notification.shift.conflict',
                    [],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => $this->conflictNotificationTitle(
                            $scheduler,
                            Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                            $shift->start,
                            $shift->end,
                            $user->language
                        ),
                        'href' => $user ? ShiftNotificationLinkService::ownOperationPlanForDate(
                            $user,
                            $shift->event_start_day
                        ) : null
                    ],
                ];

                $notificationService->setTitle($notificationTitle);
                $notificationService->setIcon('red');
                $notificationService->setPriority(2);
                $notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CONFLICT);
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
                            'user_name' => $schedulerName,
                            'scheduler_source' => $scheduler['source'],
                            'scheduled_at' => $scheduler['at'],
                            'date' => $shift?->event_start_day ?? $shift->start_date,
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
                                'user_name' => $schedulerName,
                                'scheduler_source' => $scheduler['source'],
                                'scheduled_at' => $scheduler['at'],
                                'date' => $shift?->event_start_day ?? $shift->start_date,
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

        $shiftStartDate = $shift->event_start_day ?? Carbon::parse($shift->start_date)->toDateString();
        $shiftEndDate = $shift->end_date
            ? Carbon::parse($shift->end_date)->toDateString()
            : $shiftStartDate;

        $vacations = collect();
        if ($user) {
            // Only get vacations that overlap with the shift date range
            $vacations = $user
                ->vacations()
                ->where('date', '>=', $shiftStartDate)
                ->where('date', '<=', $shiftEndDate)
                ->get();
        }

        if ($freelancer) {
            // Only get vacations that overlap with the shift date range
            $vacations = $freelancer
                ->vacations()
                ->where('date', '>=', $shiftStartDate)
                ->where('date', '<=', $shiftEndDate)
                ->get();
        }

        // Only proceed if there are actual vacation conflicts
        if ($vacations->count() === 0) {
            return;
        }

        $scheduler = $this->resolveScheduler($shift, $user ?? $freelancer);
        $schedulerName = $scheduler['name'];
        $hasConflict = false;

        if ($user) {
            $notificationTitle = __(
                'notification.shift.conflict',
                [],
                $user->language
            );
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $this->conflictNotificationTitle(
                        $scheduler,
                        Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                        $shift->start,
                        $shift->end,
                        $user->language
                    ),
                    'href' => $user ? ShiftNotificationLinkService::ownOperationPlanForDate(
                        $user,
                        $shift->event_start_day
                    ) : null
                ],
            ];

            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('red');
            $notificationService->setPriority(2);
            $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CONFLICT);
            $notificationService->setBroadcastMessage($broadcastMessage);
            $notificationService->setDescription($notificationDescription);
            $notificationService->setButtons(['see_shift']);
            $notificationService->setShiftId($shift->id);
        }

        foreach ($vacations as $vacation) {
            $vacation->conflicts()->each(function ($conflict): void {
                $conflict->delete();
            });
            // check if vacation is full_day
            if ($vacation->full_day) {
                $this->create([
                    'vacation_id' => $vacation->id,
                    'shift_id' => $shift->id,
                    'user_name' => $schedulerName,
                    'scheduler_source' => $scheduler['source'],
                    'scheduled_at' => $scheduler['at'],
                    'date' => $shift?->event_start_day ?? $shift->start_date,
                    'start_time' => $shift->start,
                    'end_time' => $shift->end,
                ]);
                $hasConflict = true;
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
                        'user_name' => $schedulerName,
                        'scheduler_source' => $scheduler['source'],
                        'scheduled_at' => $scheduler['at'],
                        'date' => $shift?->event_start_day ?? $shift->start_date,
                        'start_time' => $shift->start,
                        'end_time' => $shift->end,
                    ]);
                    $hasConflict = true;
                }
            }
        }

        // Only send one notification if there was any conflict
        if ($hasConflict && $user) {
            $notificationService->setNotificationTo($user);
            $notificationService->createNotification();
        }
    }
}
