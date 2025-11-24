<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Events\UpdateEventShiftInShiftPlan;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\AssignUserToShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use stdClass;
use Illuminate\Support\Collection as SupportCollection;

class ShiftService
{
    public function __construct(
        private readonly ShiftRepository $shiftRepository,
        private readonly CraftService $craftService,
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService,
        private readonly AvailabilityConflictService $availabilityConflictService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly ShiftUserService $shiftUserService,
        private readonly ShiftFreelancerService $shiftFreelancerService,
        private readonly ShiftServiceProviderService $shiftServiceProviderService,
        private readonly ShiftCountService $shiftCountService,
        protected AuthManager $authManager
    ) {
    }

    public function getAll(array $with = []): Collection
    {
        return $this->shiftRepository->getAll($with);
    }

    public function getById(int $shiftId): Shift|null
    {
        return $this->shiftRepository->getById($shiftId);
    }

    private function convertStartEndDate(array $data): object
    {
        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);

        return (object) [
            'start' => $start->format('Y-m-d'),
            'end' => $end->isBefore($start)
                ? $start->copy()->addDay()->format('Y-m-d')
                : $start->format('Y-m-d'),
        ];
    }

    private function convertStartEndDateByEvent(Event $event, array $data): object
    {
        // 1) Basis-Datum: bevorzugt $data['start_date'], dann $data['day'], sonst Event-Start-Datum
        $baseDate = $data['start_date']
            ?? $data['day']
            ?? Carbon::parse($event->start_time)->toDateString();

        // 2) Zeiten: bevorzugt $data['start']/$data['end'], sonst Event-Zeiten
        $startTime = $data['start'] ?? Carbon::parse($event->start_time)->format('H:i');
        $endTime   = $data['end']   ?? Carbon::parse($event->end_time ?? $event->start_time)->format('H:i');

        // 3) Kombinieren zu konkreten DateTimes auf Basis des Basisdatums
        $start = Carbon::parse("{$baseDate} {$startTime}");
        $end   = Carbon::parse("{$baseDate} {$endTime}");

        // 4) Über-Mitternacht-Fall (Ende < Start) -> Ende +1 Tag
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return (object) [
            'start' => $start->toDateString(), // start_date
            'end'   => $end->toDateString(),   // end_date
        ];
    }

    public function createShiftBySeriesEvent(Event $event, array $data, int $craftId): Shift|Model
    {
        $dates = $this->convertStartEndDate($data);

        $shift = new Shift([
            'start_date' => $dates->start,
            'end_date' => $dates->end,
            'start' => $data['start'],
            'end' => $data['end'],
            'break_minutes' => $data['break_minutes'],
            'description' => $data['description'],
            'is_committed' => false,
        ]);

        $shift->event()->associate($event);
        $shift->craft()->associate($craftId);

        return $this->save($shift);
    }

    public function createShift(Event $event, Craft $craft, array $data): Shift|Model
    {
        $dates = $this->convertStartEndDateByEvent($event, $data);

        $shift = new Shift([
            'start_date' => $dates->start,
            'end_date' => $dates->end,
            'start' => $data['start'],
            'end' => $data['end'],
            'break_minutes' => $data['break_minutes'],
            'description' => $data['description'],
        ]);

        $shift->event()->associate($event);
        $shift->craft()->associate($craft);

        return $this->save($shift);
    }

    public function createShiftByRequest(array $data, Event $event): Model|Shift
    {
        return $this->createShift(
            $event,
            $this->craftService->findById($data['craft_id']),
            $data
        );
    }

    public function createAutomatic(Event $event, int $craftId, array $data): Shift|Model
    {
        //dd($event, $data);
        $dates = $this->convertStartEndDateByEvent($event, $data);

        $shift = new Shift([
            'start_date' => $dates->start,
            'end_date' => $dates->end,
            'start' => Carbon::parse($data['start'])->format('H:i'),
            'end' => Carbon::parse($data['end'])->format('H:i'),
            'break_minutes' => $data['break_minutes'],
            'description' => $data['description'],
        ]);

        $shift->event()->associate($event);
        $shift->craft()->associate($craftId);

        return $this->save($shift);
    }

    public function createFromShiftPresetShiftForEvent(PresetShift $presetShift, Event $event): Shift
    {
        $shift = new Shift([
            'event_id' => $event->id,
            'start_date' => Carbon::parse($event->start_time)->format('Y-m-d'),
            'end_date' => Carbon::parse($event->end_time)->format('Y-m-d'),
            'start' => $presetShift->start,
            'end' => $presetShift->end,
            'break_minutes' => $presetShift->break_minutes,
            'craft_id' => $presetShift->craft_id,
            'description' => $presetShift->description,
            'is_committed' => false
        ]);

        $this->shiftRepository->save($shift);
        return $shift;
    }

    public function createShiftWithoutEventAutomatic(int $craftId, array $data, string $day): Shift|Model
    {
        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);

        $startDate = Carbon::parse($day)->format('Y-m-d');
        $endDate = $end->isBefore($start)
            ? Carbon::parse($day)->copy()->addDay()->format('Y-m-d')
            : $startDate;

        $shift = new Shift([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'break_minutes' => $data['break_minutes'],
            'description' => $data['description'],
            'room_id' => $data['room_id'],
            'project_id' => $data['project_id'],
            'shift_group_id' => $data['shift_group_id'],
        ]);

        $shift->craft()->associate($craftId);

        return $this->save($shift);
    }

    public function createShiftWithoutEvent(int $craftId, array $data): Shift|Model
    {
        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);
        $startDate = Carbon::parse($data['start_date']);

        $endDate = $end->isBefore($start)
            ? $startDate->copy()->addDay()->format('Y-m-d')
            : $startDate->format('Y-m-d');

        $shift = new Shift([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate,
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'break_minutes' => $data['break_minutes'],
            'description' => $data['description'],
            'room_id' => $data['room_id'],
        ]);

        $shift->craft()->associate($craftId);

        return $this->save($shift);
    }

    public function createRemovedAllUsersFromShiftHistoryEntry(Shift $shift, ChangeService $changeService): void
    {
        $changeService->saveFromBuilder(
            $changeService
                ->createBuilder()
                ->setType('shift')
                ->setModelClass(Shift::class)
                ->setModelId($shift->id)
                ->setShift($shift)
                ->setTranslationKey('All scheduled employees have been removed from shift')
                ->setTranslationKeyPlaceholderValues([
                    $shift->craft->abbreviation,
                    $shift->event->eventName
                ])
        );
    }

    public function delete(
        Shift $shift,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): bool {
        foreach ($shift->shiftsQualifications as $shiftsQualification) {
            $shiftsQualificationsService->delete($shiftsQualification);
        }

        foreach ($shift->users as $user) {
            $shiftUserService->delete($user->pivot);
        }

        foreach ($shift->freelancer as $freelancer) {
            $shiftFreelancerService->delete($freelancer->pivot);
        }

        foreach ($shift->serviceProvider as $serviceProvider) {
            $shiftServiceProviderService->delete($serviceProvider->pivot);
        }

        return $this->shiftRepository->delete($shift);
    }

    public function deleteShifts(
        Collection|array $shifts,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): void {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->delete(
                $shift,
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService
            );
        }
    }

    public function restoreShifts(
        Collection|array $shifts,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): void {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $shift->restore();
            $shift->shiftsQualifications()->onlyTrashed()->each(
                fn($shiftsQualification) => $shiftsQualificationsService->restore($shiftsQualification)
            );

            // restore shift users and freelancers from pivot table
            $shift->users()->each(
                fn($user) => $shiftUserService->restore($user->pivot)
            );

            $shift->freelancer()->each(
                fn($freelancer) => $shiftFreelancerService->restore($freelancer->pivot)
            );

            $shift->serviceProvider()->each(
                fn($serviceProvider) => $shiftServiceProviderService->restore($serviceProvider->pivot)
            );
        }
    }

    public function forceDelete(Shift $shift): bool
    {
        //relations are deleted on cascade
        //broadcast(new ShiftUpdated($shift))->toOthers();

        return $this->shiftRepository->forceDelete($shift);
    }

    public function forceDeleteShifts(Collection|array $shifts): void
    {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->forceDelete($shift);
        }
    }

    public function createInfringementNotification(Shift $shift): void
    {
        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT);

        $this->notificationService->setButtons(['change_shift', 'delete_shift_notification']);
        $this->notificationService->setProjectId($shift->event()->first()->project()->first()->id);
        $this->notificationService->setEventId($shift->event()->first()->id);
        $this->notificationService->setShiftId($shift->id);
        foreach (User::role(RoleEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
            $notificationTitle = __('notification.shift.short_break', [], $authUser->language);
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
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

    public function save(Shift $shift): Shift
    {
        // Nur relevant, wenn Schicht bereits existiert
        $hadOriginal = $shift->exists;

        $originalStartDate = $hadOriginal ? $shift->getOriginal('start_date') : null;
        $originalEndDate   = $hadOriginal ? $shift->getOriginal('end_date')   : null;
        $originalStart     = $hadOriginal ? $shift->getOriginal('start')      : null;
        $originalEnd       = $hadOriginal ? $shift->getOriginal('end')        : null;

        // Vor dem Speichern checken, ob sich Zeit-Felder geändert haben
        $timeWasDirty = $hadOriginal && $shift->isDirty(['start_date', 'end_date', 'start', 'end']);

        /** @var Shift $savedShift */
        $savedShift = $this->shiftRepository->save($shift);

        if ($timeWasDirty && $originalStartDate && $originalEndDate && $originalStart && $originalEnd) {
            $this->syncPivotTimesAndLogCommittedChanges(
                $savedShift,
                $originalStartDate,
                $originalEndDate,
                $originalStart,
                $originalEnd
            );
        }

        return $savedShift;
    }

    /**
     * Sync Pivot-Zeiten (User/Freelancer/ServiceProvider), wenn sie noch exakt
     * der alten Schichtzeit entsprechen und ggf. CommittedShiftChange-Logs erzeugen.
     */
    protected function syncPivotTimesAndLogCommittedChanges(
        Shift $shift,
        mixed $originalStartDate,
        mixed $originalEndDate,
        mixed $originalStart,
        mixed $originalEnd,
    ): void {
        // Alte und neue Zeiten als Carbon kombinieren
        $oldStart = $this->combineDateAndTime($originalStartDate, $originalStart);
        $oldEnd   = $this->combineDateAndTime($originalEndDate, $originalEnd);

        $newStart = $this->combineDateAndTime($shift->start_date, $shift->start);
        $newEnd   = $this->combineDateAndTime($shift->end_date, $shift->end);

        if (! $oldStart || ! $oldEnd || ! $newStart || ! $newEnd) {
            return;
        }

        $oldWorkTime = $oldEnd->diffInMinutes($oldStart);
        $newWorkTime = $newEnd->diffInMinutes($newStart);

        $changedByUserId = Auth::id();

        // Relationen laden inkl. Pivot
        $shift->load(['users', 'freelancer', 'serviceProvider']);

        // Flag, ob Schicht committed ist (nach Änderung)
        $isCommitted = (bool) $shift->is_committed;

        // 1) Users (shift_user pivot)
        foreach ($shift->users as $user) {
            $pivot = $user->pivot;

            $pivotStart = $this->combineDateAndTime($pivot->start_date, $pivot->start_time);
            $pivotEnd   = $this->combineDateAndTime($pivot->end_date, $pivot->end_time);

            // Nur anfassen, wenn Pivot-Zeit exakt der alten Schichtzeit entspricht
            if (! $pivotStart || ! $pivotEnd) {
                continue;
            }

            if ($pivotStart->equalTo($oldStart) && $pivotEnd->equalTo($oldEnd)) {
                // Pivot auf neue Zeiten setzen
                $pivot->start_date = $newStart->toDateString();
                $pivot->end_date   = $newEnd->toDateString();
                $pivot->start_time = $newStart;
                $pivot->end_time   = $newEnd;
                $pivot->save();

                if ($isCommitted) {
                    CommittedShiftChange::create([
                        'craft_id'                => $shift->craft_id,
                        'shift_id'                => $shift->getKey(),
                        'subject_type'            => Shift::class,
                        'subject_id'              => $shift->getKey(),
                        'change_type'             => 'shift_time_updated',
                        'field_changes'           => [
                            'work_time' => [
                                'old' => $oldWorkTime,
                                'new' => $newWorkTime,
                            ],
                            'start' => [
                                'old' => $oldStart->toDateTimeString(),
                                'new' => $newStart->toDateTimeString(),
                            ],
                            'end' => [
                                'old' => $oldEnd->toDateTimeString(),
                                'new' => $newEnd->toDateTimeString(),
                            ],
                        ],
                        'affected_user_type'     => User::class,
                        'affected_user_id'       => $user->id,
                        'changed_by_user_id'      => $changedByUserId,
                        'changed_at'              => now(),
                        'acknowledged_at'         => null,
                        'acknowledged_by_user_id' => null,
                    ]);
                }
            }
        }

        // 2) Freelancer (shifts_freelancers pivot)
        foreach ($shift->freelancer as $freelancer) {
            $pivot = $freelancer->pivot;

            $pivotStart = $this->combineDateAndTime($pivot->start_date, $pivot->start_time);
            $pivotEnd   = $this->combineDateAndTime($pivot->end_date, $pivot->end_time);

            if (! $pivotStart || ! $pivotEnd) {
                continue;
            }

            if ($pivotStart->equalTo($oldStart) && $pivotEnd->equalTo($oldEnd)) {
                $pivot->start_date = $newStart->toDateString();
                $pivot->end_date   = $newEnd->toDateString();
                $pivot->start_time = $newStart;
                $pivot->end_time   = $newEnd;
                $pivot->save();

                if ($isCommitted) {
                    CommittedShiftChange::create([
                        'craft_id'                => $shift->craft_id,
                        'shift_id'                => $shift->getKey(),
                        'subject_type'            => Shift::class,
                        'subject_id'              => $shift->getKey(),
                        'change_type'             => 'shift_time_updated',
                        'field_changes'           => [
                            'work_time' => [
                                'old' => $oldWorkTime,
                                'new' => $newWorkTime,
                            ],
                            'start' => [
                                'old' => $oldStart->toDateTimeString(),
                                'new' => $newStart->toDateTimeString(),
                            ],
                            'end' => [
                                'old' => $oldEnd->toDateTimeString(),
                                'new' => $newEnd->toDateTimeString(),
                            ],
                        ],
                        'affected_user_type'     => \Artwork\Modules\Freelancer\Models\Freelancer::class,
                        'affected_user_id'       => $freelancer->id,
                        'changed_by_user_id'      => $changedByUserId,
                        'changed_at'              => now(),
                        'acknowledged_at'         => null,
                        'acknowledged_by_user_id' => null,
                    ]);
                }
            }
        }

        // 3) ServiceProvider (shifts_service_providers pivot)
        foreach ($shift->serviceProvider as $serviceProvider) {
            $pivotStart = $this->combineDateAndTime(
                $serviceProvider->pivot->start_date,
                $serviceProvider->pivot->start_time
            );
            $pivotEnd   = $this->combineDateAndTime(
                $serviceProvider->pivot->end_date,
                $serviceProvider->pivot->end_time
            );

            if (! $pivotStart || ! $pivotEnd) {
                continue;
            }

            if ($pivotStart->equalTo($oldStart) && $pivotEnd->equalTo($oldEnd)) {
                $serviceProvider->pivot->start_date = $newStart->toDateString();
                $serviceProvider->pivot->end_date   = $newEnd->toDateString();
                $serviceProvider->pivot->start_time = $newStart;
                $serviceProvider->pivot->end_time   = $newEnd;
                $serviceProvider->pivot->save();

                if ($isCommitted) {
                    CommittedShiftChange::create([
                        'craft_id'                => $shift->craft_id,
                        'shift_id'                => $shift->getKey(),
                        'subject_type'            => Shift::class,
                        'subject_id'              => $shift->getKey(),
                        'change_type'             => 'shift_time_updated',
                        'field_changes'           => [
                            'work_time' => [
                                'old' => $oldWorkTime,
                                'new' => $newWorkTime,
                            ],
                            'start' => [
                                'old' => $oldStart->toDateTimeString(),
                                'new' => $newStart->toDateTimeString(),
                            ],
                            'end' => [
                                'old' => $oldEnd->toDateTimeString(),
                                'new' => $newEnd->toDateTimeString(),
                            ],
                        ],
                        'affected_user_type'     => \Artwork\Modules\Shift\Models\ShiftServiceProvider::class,
                        'affected_user_id'       => $serviceProvider->id,
                        'changed_by_user_id'      => $changedByUserId,
                        'changed_at'              => now(),
                        'acknowledged_at'         => null,
                        'acknowledged_by_user_id' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Hilfsfunktion: Date + Time (irgendwas: string|Carbon) zu einem Carbon kombinieren.
     */
    protected function combineDateAndTime(mixed $date, mixed $time): ?Carbon
    {
        if (! $date || ! $time) {
            return null;
        }

        $dateCarbon = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $timeCarbon = $time instanceof Carbon ? $time->copy() : Carbon::parse($time);

        return Carbon::parse(
            $dateCarbon->format('Y-m-d') . ' ' . $timeCarbon->format('H:i:s')
        );
    }

    public function detachFromShifts(
        \Illuminate\Support\Collection $shifts,
        string $modelClass,
        Model $entityModel
    ): void {
        foreach ($shifts as $shift) {
            match ($modelClass) {
                User::class => $this->shiftUserService->removeFromShiftByUserIdAndShiftId(
                    $entityModel->id,
                    $shift->id,
                    $this->notificationService,
                    $this->shiftCountService,
                    $this->vacationConflictService,
                    $this->availabilityConflictService,
                    $this->changeService
                ),
                Freelancer::class => $this->shiftFreelancerService->removeFromShiftByUserIdAndShiftId(
                    $entityModel->id,
                    $shift->id,
                    $this->notificationService,
                    $this->shiftCountService,
                    $this->vacationConflictService,
                    $this->availabilityConflictService,
                    $this->changeService
                ),
                ServiceProvider::class => $this->shiftServiceProviderService->removeFromShiftByUserIdAndShiftId(
                    $entityModel->id,
                    $shift->id,
                    $this->shiftCountService,
                    $this->changeService
                ),
            };
        }
    }

    public function commitShiftsByDate(Carbon $startDate, Carbon $endDate, int $craftId): void
    {
        $shifts = Shift::whereBetween('start_date', [$startDate, $endDate])->where('craft_id', $craftId)->get();

        if ($shifts->isEmpty()) {
            return;
        }

        $firstShift = $shifts->first();
        $lastShift = $shifts->last();

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_LOCKED);

        $userIdHasGetNotification = [];

        foreach ($shifts as $shift) {
            $shift->is_committed = true;
            $shift->committing_user_id = Auth::id();
            $shift->save();
            broadcast(new UpdateEventShiftInShiftPlan($shift, $shift->room_id ?? $shift->event->room_id));

            foreach ($shift->users as $user) {
                if (!in_array($user->id, $userIdHasGetNotification)) {
                    $userIdHasGetNotification[] = $user->id;

                    $notificationTitle = __('notification.shift.locked');
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __(
                                'notification.keyWords.concerns_time_period',
                                [
                                    'start' => $firstShift->start_date->format('d.m.Y H:i'),
                                    'end' => $lastShift->end_date->format('d.m.Y H:i'),
                                ],
                                $user->language
                            ),
                            'href' => null
                        ],
                    ];

                    $broadcastMessage = [
                        'id' => random_int(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];

                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                }
            }
        }
    }

    public function handleGlobalQualificationChange(SupportCollection $globalQualification, Shift $shift): void
    {
        if ($globalQualification->isEmpty()) {
            return;
        }

        $recorder = app(ShiftChangeRecorder::class);

        $shift->load('globalQualifications');
        $before = $shift->globalQualifications
            ->pluck('pivot.quantity', 'id')
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->toArray();

        $syncPayload = $globalQualification
            ->filter(fn ($item) => !empty($item['global_qualification_id']))
            ->mapWithKeys(fn ($item) => [
                (int) $item['global_qualification_id'] => ['quantity' => (int) $item['quantity']],
            ])
            ->toArray();

        $shift->globalQualifications()->sync($syncPayload);

        $shift->load('globalQualifications');
        $after = $shift->globalQualifications
            ->pluck('pivot.quantity', 'id')
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->toArray();

        $recorder->recordGlobalQualificationDiff($shift, $before, $after);
        $allIds = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($allIds as $id) {
            $old = $before[$id] ?? 0;
            $new = $after[$id] ?? 0;

            if ($old === $new) {
                continue;
            }

            $qualification = GlobalQualification::find($id);

            $this->logActivity($shift, $qualification, $old, $new);
        }
    }

    protected function logActivity(Shift $shift, GlobalQualification $qualification, $old, $new): void
    {
        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->authManager->user())
            ->event('updated')
            ->tap(function (Activity $activity) use ($shift, $qualification, $old, $new): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => 'Global qualification {0} changed from {1} to {2} for shift {3}',
                    'translation_key_placeholder_values' => [
                        $qualification?->name ?? 'Unbenannte Qualifikation',
                        $old,
                        $new,
                        $shift->craft->name . ' (' . $shift->craft->abbreviation . ')',
                    ],
                    'context'            => $shift->is_committed
                        ? 'post_commit'
                        : ($shift->in_workflow ? 'in_workflow' : 'normal'),
                    'shift_id'           => $shift->id,
                    'craft_id'           => $shift->craft_id,
                    'project_id'         => $shift->project_id,
                    'current_request_id' => $shift->current_request_id,
                    'global_qualification_id'   => $qualification?->id,
                    'global_qualification_name' => $qualification?->name,
                    'old_quantity'              => $old,
                    'new_quantity'              => $new,
                ]);
            })
            ->log('Global qualification quantity updated');
    }
}
