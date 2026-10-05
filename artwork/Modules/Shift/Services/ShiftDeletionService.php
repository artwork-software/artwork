<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Events\DestroyShift;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Einziger Weg, eine Schicht zu löschen (Papierkorb): Einzel- und Bulk-Löschen, Zellen im
 * Mehrfach-Bearbeiten, Termin/Projekt/Gewerk löschen. Vorher erledigte jeder Pfad einen anderen Teil
 * der Folgearbeiten — Benachrichtigung, Konflikte, Projekt-Tageszuordnungen, Regel-Neuprüfung und
 * Live-Update laufen jetzt überall gleich.
 */
class ShiftDeletionService
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService,
        private readonly ShiftRuleService $shiftRuleService,
    ) {
    }

    /**
     * @return array<string, mixed>|null Payload wie der DestroyShift-Broadcast (für die eigene Ansicht)
     */
    public function delete(Shift $shift, bool $notifyAffectedPeople = true): ?array
    {
        $result = $this->deleteOne($shift, $notifyAffectedPeople, true);
        if ($result === null) {
            return null;
        }

        $this->revalidateShiftRules($result['users'], $result['start'], $result['end']);

        return $result['payload'];
    }

    /**
     * Mehrere Schichten löschen. Regeln werden am Ende EINMAL je betroffener Person über den gesamten
     * Zeitraum neu geprüft. $broadcastEach = false für Massenpfade (Projekt/Serie/Gewerk löschen): kein
     * Live-Update je Schicht — früher die Hauptursache für Timeouts; die Aufrufer schicken danach
     * einen gemeinsamen OccupancyUpdated-Ping.
     *
     * @param iterable<Shift> $shifts
     * @return array<int, array<string, mixed>>
     */
    public function deleteMany(iterable $shifts, bool $notifyAffectedPeople = true, bool $broadcastEach = true): array
    {
        $payloads = [];
        /** @var array<int, array{user: User, start: Carbon, end: Carbon}> $rangesByUser */
        $rangesByUser = [];

        foreach ($shifts as $shift) {
            $result = $this->deleteOne($shift, $notifyAffectedPeople, $broadcastEach);
            if ($result === null) {
                continue;
            }
            if ($result['payload'] !== null) {
                $payloads[] = $result['payload'];
            }
            foreach ($result['users'] as $user) {
                $range = $rangesByUser[$user->id]
                    ?? ['user' => $user, 'start' => $result['start'], 'end' => $result['end']];
                $range['start'] = $result['start']->lt($range['start']) ? $result['start'] : $range['start'];
                $range['end'] = $result['end']->gt($range['end']) ? $result['end'] : $range['end'];
                $rangesByUser[$user->id] = $range;
            }
        }

        foreach ($rangesByUser as $range) {
            $this->shiftRuleService->validateRulesForUser(
                $range['user'],
                $range['start']->copy(),
                $range['end']->copy()
            );
        }

        return $payloads;
    }

    /**
     * @return array{payload: array<string, mixed>|null, users: Collection<int, User>, start: Carbon, end: Carbon}|null
     */
    private function deleteOne(Shift $shift, bool $notifyAffectedPeople, bool $broadcast): ?array
    {
        if ($shift->trashed()) {
            return null;
        }

        $shift->loadMissing(['craft', 'project', 'event.project']);
        $affectedUsers = $shift->users()->get();
        $affectedWorkers = $broadcast ? $this->affectedWorkersPayload($shift) : [];
        $shiftStart = Carbon::parse($shift->start_date);
        $shiftEnd = Carbon::parse($shift->end_date ?? $shift->start_date);
        $roomId = $shift->event_id ? $shift->event?->room_id : $shift->room_id;

        if ($shift->is_committed && $notifyAffectedPeople) {
            $this->notifyCommittedShiftDeleted($shift, $affectedUsers);
        }

        DB::transaction(function () use ($shift): void {
            // Konflikte hängen an der Schicht; vacation_conflicts hat keinen Fremdschlüssel und
            // das Soft-Delete löst keine Kaskade aus — sonst bliebe der Urlaub als "mit Konflikt" markiert.
            VacationConflict::query()->where('shift_id', $shift->id)->get()->each->delete();
            AvailabilitiesConflict::query()->where('shift_id', $shift->id)->get()->each->delete();

            $this->shiftService->restoreSupersededProjectDayAssignments($shift);
            $this->shiftService->delete($shift, $this->shiftsQualificationsService);
        });

        $payload = null;
        if ($broadcast && $roomId !== null) {
            $event = new DestroyShift($shift, (int) $roomId, $affectedWorkers);
            $payload = $event->broadcastWith();
            SafeBroadcast::send($event);
        }

        return ['payload' => $payload, 'users' => $affectedUsers, 'start' => $shiftStart, 'end' => $shiftEnd];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function affectedWorkersPayload(Shift $shift): array
    {
        $workers = [];
        $relationTypes = ['users' => 'user', 'freelancer' => 'freelancer', 'serviceProvider' => 'service_provider'];
        foreach ($relationTypes as $relation => $type) {
            foreach ($shift->{$relation}()->get() as $worker) {
                $workers[] = ['id' => $worker->id, 'type' => $type];
            }
        }

        return $workers;
    }

    /**
     * @param Collection<int, User> $users
     */
    private function revalidateShiftRules(Collection $users, Carbon $start, Carbon $end): void
    {
        foreach ($users as $user) {
            $this->shiftRuleService->validateRulesForUser($user, $start->copy(), $end->copy());
        }
    }

    /**
     * @param Collection<int, User> $affectedUsers
     */
    private function notifyCommittedShiftDeleted(Shift $shift, Collection $affectedUsers): void
    {
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

        // Besetzung + Planer:innen des Gewerks (Fallback: Gewerksverantwortliche), ohne Doppelte
        $recipients = $affectedUsers
            ->concat($this->craftPlannersToNotify($shift->craft))
            ->unique('id')
            ->reject(static fn (User $user): bool => $user->id === Auth::id());

        foreach ($recipients as $user) {
            $notificationTitle = __(
                'notification.shift.deleted_where_locked',
                [
                    'projectName' => $shift->project?->name
                        ?? $shift->event?->project?->name
                        ?? __('notification.shift.without_project'),
                    'craftAbbreviation' => $shift->craft?->abbreviation,
                ],
                $user->language
            );

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => 'error',
                'message' => $notificationTitle,
            ]);
            $this->notificationService->setDescription([
                1 => [
                    'type' => 'string',
                    'title' => __('notification.keyWords.concerns_shift', [], $user->language)
                        . $shift->time_span_label,
                    'href' => null,
                ],
            ]);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function craftPlannersToNotify(?Craft $craft): Collection
    {
        if ($craft === null) {
            return new Collection();
        }

        $planners = $craft->craftShiftPlaner()->get();

        return $planners->isEmpty() ? $craft->managingUsers()->get() : $planners;
    }
}
