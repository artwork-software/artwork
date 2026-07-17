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
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Events\UpdateEventShiftInShiftPlan;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\AssignUserToShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        private readonly WorkingHourCacheService $workingHourCacheService,
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
            'project_id' => $this->resolveExistingProjectId($data['project_id'] ?? null),
            'shift_group_id' => $data['shift_group_id'] ?? null,
        ]);

        $shift->craft()->associate($craftId);

        return $this->save($shift);
    }

    /**
     * Das Frontend kann eine veraltete project_id liefern (z.B. wenn das Projekt
     * zwischenzeitlich gelöscht wurde oder der Suchindex noch nicht aktualisiert ist).
     * Eine nicht (mehr) existierende project_id würde die FK-Constraint
     * shifts_project_id_foreign verletzen und den Request mit einem 500 abbrechen.
     * Da der FK ON DELETE SET NULL ist, ist null ein gültiger Zustand – wir
     * normalisieren eine unbekannte project_id daher auf null.
     */
    private function resolveExistingProjectId(int|string|null $projectId): ?int
    {
        if ($projectId === null || $projectId === '') {
            return null;
        }

        return Project::whereKey($projectId)->exists() ? (int) $projectId : null;
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
        $this->workingHourCacheService->forgetForShift($shift);

        // Die "Betroffen: …"-Namen für den Lösch-Verlaufseintrag JETZT erfassen:
        // gleich werden die shift_workers-Pivots soft-deleted, danach liefern die
        // users()/freelancer()/serviceProvider()-Relationen (whereNull deleted_at)
        // nichts mehr — der deleting-Observer käme zu spät.
        $shift->captureDeletionAffectedWorkers();

        foreach ($shift->shiftsQualifications as $shiftsQualification) {
            // Kaskade: die Schicht selbst wird gleich gelöscht und bekommt ihren
            // eigenen Verlaufseintrag — einzelne "Schichtplatz entfernt"-Einträge
            // wären nur Rauschen daneben.
            $shiftsQualification->deletingViaShiftCascade = true;
            $shiftsQualificationsService->delete($shiftsQualification);
        }

        // Worker assignments (users, freelancers and service providers) all live in the
        // unified shift_workers pivot (ShiftWorker). Delete them in one go. The legacy
        // per-type services still expect the old ShiftUser/ShiftFreelancer/
        // ShiftServiceProvider pivots and would throw a TypeError when handed a
        // ShiftWorker instance, which aborted the whole project/event deletion cascade.
        ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->get()
            ->each(static fn (ShiftWorker $shiftWorker): ?bool => $shiftWorker->delete());

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

            // Worker assignments (users, freelancers, service providers) all live in the unified
            // shift_workers pivot (ShiftWorker) and were soft-deleted together with the shift.
            // Restore them directly: the users()/freelancer()/serviceProvider() relations exclude
            // soft-deleted rows, and the legacy per-type services expect the old pivot models.
            ShiftWorker::onlyTrashed()
                ->where('shift_id', $shift->id)
                ->get()
                ->each(static fn (ShiftWorker $shiftWorker): ?bool => $shiftWorker->restore());
        }
    }

    public function forceDelete(Shift $shift): bool
    {
        $this->workingHourCacheService->forgetForShift($shift);

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
        // Nullsafe: Schichten ohne Event bzw. Events ohne Projekt crashten hier sonst
        // mit "Attempt to read property on null" — und rissen das Anlegen der Schicht mit.
        $this->notificationService->setProjectId($shift->event?->project?->id);
        $this->notificationService->setEventId($shift->event?->id);
        $this->notificationService->setShiftId($shift->id);
        foreach (User::role(RoleEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
            $notificationTitle = __('notification.shift.short_break', [], $authUser->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => __('notification.keyWords.concerns') .
                        ($shift->event?->project?->name ?? __('notification.shift.without_project')) . ' , ' .
                        ($shift->craft?->abbreviation ?? '') . ' ' .
                        $shift->time_span_label,
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
        $breakWasDirty = $hadOriginal && $shift->isDirty(['break_minutes']);

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

            $this->workingHourCacheService->forgetForShift($savedShift);
        } elseif ($breakWasDirty) {
            $this->workingHourCacheService->forgetForShift($savedShift);
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

        // Carbon 3: diffInMinutes ist vorzeichenbehaftet — Start->Ende statt
        // Ende->Start, sonst landen negative Minuten in work_time.old/new.
        $oldWorkTime = $oldStart->diffInMinutes($oldEnd);
        $newWorkTime = $newStart->diffInMinutes($newEnd);

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

    public function commitShiftsByDate(
        Carbon $startDate,
        Carbon $endDate,
        int $craftId,
        ?int $weekNumber = null,
        ?int $year = null
    ): void {
        // orderBy: first()/last() müssen den tatsächlichen Zeitraum liefern —
        // ohne Sortierung war der in der Notification genannte Zeitraum zufällig.
        $shifts = Shift::whereBetween('start_date', [$startDate, $endDate])
            ->where('craft_id', $craftId)
            ->orderBy('start_date')
            ->get();

        if ($shifts->isEmpty()) {
            return;
        }

        $firstShift = $shifts->first();
        $lastShift = $shifts->sortBy('end_date')->last();

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_LOCKED);

        $userIdHasGetNotification = [];

        foreach ($shifts as $shift) {
            $shift->is_committed = true;
            $shift->committing_user_id = Auth::id();
            $shift->save();

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
                                    // DATE-Spalten: H:i zeigte immer "00:00";
                                    // end_date ist nullable → auf start_date zurückfallen
                                    'start' => $firstShift->start_date?->format('d.m.Y') ?? '',
                                    'end' => ($lastShift->end_date ?? $lastShift->start_date)?->format('d.m.Y') ?? '',
                                ],
                                $user->language
                            ),
                            'href' => null
                        ],
                    ];

                    $broadcastMessage = [
                        'id' => Str::uuid()->toString(),
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

        // UpdateEventShiftInShiftPlan ist ShouldBroadcastNow: jeder broadcast() ist ein
        // synchroner HTTP-Call an Reverb + Relation-Loads für das DTO. Deshalb erst nach
        // dem Response ausführen (läuft im selben Prozess, braucht keinen Queue-Worker).
        dispatch(function () use ($shifts): void {
            foreach ($shifts as $shift) {
                $roomId = $shift->room_id ?? $shift->event?->room_id;
                if ($roomId !== null) {
                    broadcast(new UpdateEventShiftInShiftPlan($shift, $roomId));
                }
            }
        })->afterResponse();

        // is_committed ist nicht in logOnly — ohne den Sammel-Eintrag wäre die
        // Festschreibung im Schichtverlauf komplett unsichtbar.
        $this->logCommitSummaryActivity($shifts, true, $weekNumber, $year);
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

    /**
     * Schreibt EINEN Sammel-Eintrag für eine Festschreibungs-Aktion in den
     * Schichtverlauf (statt eines Eintrags pro Schicht): "Festschreibung KW 24,
     * Sound (12 Schichten)". Der Eintrag hat bewusst KEIN Subject — er gehört zur
     * Aktion, nicht zu einer einzelnen Schicht. Der ShiftHistoryController sammelt
     * ihn über properties->commit_summary (Zeitraum-Überlappung + craft_ids) ein.
     */
    public function logCommitSummaryActivity(
        Collection $shifts,
        bool $committed,
        ?int $weekNumber = null,
        ?int $year = null
    ): void {
        if ($shifts->isEmpty()) {
            return;
        }

        $shifts->loadMissing('craft:id,name,abbreviation');

        $startDate = $shifts->min('start_date')?->format('Y-m-d');
        $endDate = $shifts->map(
            static fn (Shift $shift) => $shift->end_date ?? $shift->start_date
        )->filter()->max()?->format('Y-m-d');

        $craftNames = $shifts->map(
            static fn (Shift $shift) => $shift->craft?->name
        )->filter()->unique()->values();
        $craftIds = $shifts->pluck('craft_id')->filter()->unique()->values()->all();

        $periodLabel = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d.m.Y')
            : Carbon::parse($startDate)->format('d.m.Y') . ' – ' . Carbon::parse($endDate)->format('d.m.Y');

        if ($committed && $weekNumber !== null) {
            $translationKey = 'Shifts committed: calendar week {0} – {1} ({2} shifts)';
            $placeholderValues = ["{$weekNumber}/{$year}", $craftNames->implode(', '), $shifts->count()];
        } elseif ($committed) {
            $translationKey = 'Shifts committed: {0} – {1} ({2} shifts)';
            $placeholderValues = [$periodLabel, $craftNames->implode(', '), $shifts->count()];
        } else {
            $translationKey = 'Shift commitment revoked: {0} – {1} ({2} shifts)';
            $placeholderValues = [$periodLabel, $craftNames->implode(', '), $shifts->count()];
        }

        activity('shift')
            ->causedBy($this->authManager->user())
            ->event($committed ? 'committed_bulk' : 'uncommitted_bulk')
            ->tap(function (Activity $activity) use (
                $shifts,
                $committed,
                $weekNumber,
                $year,
                $startDate,
                $endDate,
                $craftNames,
                $craftIds,
                $translationKey,
                $placeholderValues
            ): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => $translationKey,
                    'translation_key_placeholder_values' => $placeholderValues,
                    'context' => 'commit',
                    'craft_ids' => $craftIds,
                    'shift_ids' => $shifts->pluck('id')->all(),
                    'commit_summary' => [
                        'committed' => $committed,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'week' => $weekNumber,
                        'year' => $year,
                        'crafts' => $craftNames->all(),
                        'count' => $shifts->count(),
                    ],
                ]);
            })
            ->log($committed ? 'Shifts committed' : 'Shift commitment revoked');
    }

    /**
     * Verlaufseintrag für das Festschreiben/Aufheben einer EINZELNEN Schicht
     * (Einzel-Toggle) — is_committed ist nicht in logOnly, ohne diesen Eintrag
     * wäre der Vorgang im Schichtverlauf unsichtbar.
     */
    public function logSingleCommitActivity(Shift $shift, bool $committed): void
    {
        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->authManager->user())
            ->event($committed ? 'committed' : 'uncommitted')
            ->tap(function (Activity $activity) use ($shift, $committed): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => $committed
                        ? 'Shift was committed'
                        : 'Shift commitment was revoked',
                    'translation_key_placeholder_values' => [],
                    'context' => 'commit',
                    'shift_id' => $shift->id,
                    'craft_id' => $shift->craft_id,
                    'shift_snapshot' => $shift->toActivitySnapshot(),
                ]);
            })
            ->log($committed ? 'Shift was committed' : 'Shift commitment was revoked');
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
