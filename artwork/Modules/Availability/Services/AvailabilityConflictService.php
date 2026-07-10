<?php

namespace Artwork\Modules\Availability\Services;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Repositories\AvailabilityConflictRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AvailabilityConflictService
{
    public function __construct(private AvailabilityConflictRepository $availabilityConflictRepository)
    {
    }

    public function create(array $data): void
    {
        $conflict = new AvailabilitiesConflict();
        $conflict->fill($data);
        $this->availabilityConflictRepository->save($conflict);
    }

    /**
     * Name der Person, die den Worker dieser Schicht zugewiesen hat — nicht der
     * Festschreibende: bei Bulk-Festschreiben/Workflow-Genehmigung stand sonst
     * eine unbeteiligte Person im Konflikt-Hinweis. committedBy bleibt nur als
     * Fallback für Alt-Zuweisungen ohne assigned_by_user_id.
     */
    private function resolveSchedulerName(Shift $shift, User|Freelancer|null $worker): ?string
    {
        if ($worker !== null) {
            $assignedBy = ShiftWorker::byEmployableIdAndShiftId(
                $worker instanceof User ? User::class : Freelancer::class,
                $worker->id,
                $shift->id
            )->first()?->assignedBy;

            if ($assignedBy !== null) {
                return $assignedBy->full_name;
            }
        }

        return $shift->committedBy()->first()?->full_name;
    }

    //@todo: fix phpcs error - fix nesting level
    //phpcs:ignore Generic.Metrics.NestingLevel.TooHigh
    public function checkAvailabilityConflictsOnDay(
        string $day,
        NotificationService $notificationService,
        ?User $user = null,
        ?Freelancer $freelancer = null,
    ): void {
        $shifts = collect();
        $availabilities = collect();
        if ($user) {
            $shifts = $user->shifts()->where('event_start_day', $day)->isCommitted()->get();
            $availabilities = $user
                ->availabilities()
                ->where('date', $day)
                ->get();
        }

        if ($freelancer) {
            $shifts = $freelancer->shifts()->where('event_start_day', $day)->isCommitted()->get();
            $availabilities = $freelancer
                ->availabilities()
                ->where('date', $day)
                ->get();
        }

        foreach ($shifts as $shift) {
            $schedulerName = $this->resolveSchedulerName($shift, $user ?? $freelancer);
            if ($user) {
                $notificationTitle = __(
                    'notification.shift.conflict',
                    [],
                    $user?->language ?? app()->getFallbackLocale()
                );
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __(
                            'notification.shift.conflict_text',
                            [
                                'username' => $schedulerName,
                                'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                                'from' => $shift->start,
                                'to' => $shift->end
                            ],
                            $user?->language ?? app()->getFallbackLocale()
                        ),
                        'href' => null
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
            if ($availabilities->count() > 0) {
                foreach ($availabilities as $availability) {
                    $availability->conflicts()->each(function ($conflict): void {
                        $conflict->delete();
                    });
                    // check if vacation is full_day
                    if (!$availability->full_day) {
                        // check if shift is on vacation time
                        $shiftStart = Carbon::parse($shift->start);
                        $shiftEnd = Carbon::parse($shift->end);
                        $availabilityStart = Carbon::parse($availability->start_time);
                        $availabilityEnd = Carbon::parse($availability->end_time);
                        if (
                            $shiftEnd->lessThanOrEqualTo($availabilityStart) ||
                            $shiftStart->greaterThanOrEqualTo($availabilityEnd)
                        ) {
                            $this->create([
                                'availability_id' => $availability->id,
                                'shift_id' => $shift->id,
                                'user_name' => $schedulerName,
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

    public function checkAvailabilityConflictsShifts(
        Shift $shift,
        NotificationService $notificationService,
        ?User $user = null,
        ?Freelancer $freelancer = null,
    ): void {

        $shiftStartDate = $shift->event_start_day ?? Carbon::parse($shift->start_date)->toDateString();
        $shiftEndDate = $shift->end_date
            ? Carbon::parse($shift->end_date)->toDateString()
            : $shiftStartDate;

        $availabilities = collect();
        if ($user) {
            $availabilities = $user
                ->availabilities()
                ->where('date', '>=', $shiftStartDate)
                ->where('date', '<=', $shiftEndDate)
                ->get();
        }

        if ($freelancer) {
            $availabilities = $freelancer
                ->availabilities()
                ->where('date', '>=', $shiftStartDate)
                ->where('date', '<=', $shiftEndDate)
                ->get();
        }

        $shiftDate = $shiftStartDate;

        $schedulerName = $this->resolveSchedulerName($shift, $user ?? $freelancer);
        if ($user) {
            $notificationTitle = __(
                'notification.shift.conflict',
                [],
                $user->language ?? app()->getFallbackLocale()
            );
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => __(
                        'notification.shift.conflict_text',
                        [
                            'username' => $schedulerName,
                            'date' => Carbon::parse($shiftDate)->format('d.m.Y'),
                            'from' => $shift->start,
                            'to' => $shift->end
                        ],
                        $user->language ?? app()->getFallbackLocale()
                    ),
                    'href' => null
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
        if ($availabilities->count() > 0) {
            foreach ($availabilities as $availability) {
                $availability->conflicts()->each(function ($conflict): void {
                    $conflict->delete();
                });
                $shiftStart = Carbon::parse($shift->start);
                $shiftEnd = Carbon::parse($shift->end);
                $availabilityStart = Carbon::parse($availability->start_time);
                $availabilityEnd = Carbon::parse($availability->end_time);
                if (!$availability->full_day) {
                    if (
                        $shiftEnd->lessThanOrEqualTo($availabilityStart) ||
                        $shiftStart->greaterThanOrEqualTo($availabilityEnd)
                    ) {
                        $this->create([
                            'availability_id' => $availability->id,
                            'shift_id' => $shift->id,
                            'user_name' => $schedulerName,
                            'date' => $shiftDate,
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
