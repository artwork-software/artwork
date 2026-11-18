<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

/**
 * Zeit- und Raumänderungen an fest gebuchten Terminen durch Personen ohne Direktbuchrecht im Raum
 * (Kundenfeedback 18.11.2026):
 * - bleibt der Termin im selben Raum und innerhalb der bestätigten Belegung (z. B. verkürzt), bleibt er fest;
 * - geht er darüber hinaus (länger, anderer Tag, anderer Raum), wird er zur Raumanfrage.
 *
 * Verglichen wird mit der ursprünglich bestätigten Belegung, nicht mit dem letzten Stand: wer erst verkürzt und
 * dann wieder auf den alten Zeitraum verlängert, braucht keine neue Anfrage. Die Belegung steht in
 * approved_room_id/approved_start_time/approved_end_time, aber nur solange sie vom aktuellen Stand abweicht;
 * leer heißt „der aktuelle Stand ist bestätigt“. Wird eine Änderungsanfrage abgelehnt, springt der Termin auf
 * diese Belegung zurück (EventController::declineEvent).
 *
 * Alle Wege, die Zeit oder Raum ändern (Termin-Dialog, Serien, Terminliste, Mehrfachbearbeitung, Verschieben im
 * Kalender, KI), laufen hier durch: snapshot() vor der Änderung, evaluate() bzw. applyAfterChange() danach. Die
 * Benachrichtigung der Raumadmins bleibt beim Aufrufer, der sie dort bündelt.
 */
class EventChangeApprovalService
{
    /**
     * Raumanfrage zugesagt: der aktuelle Stand ist die bestätigte Belegung (für Query-Updates).
     */
    public const CLEARED_APPROVAL = [
        'approved_room_id' => null,
        'approved_start_time' => null,
        'approved_end_time' => null,
    ];

    public function __construct(
        private readonly EventSettingsService $eventSettingsService,
    ) {
    }

    /**
     * @return array{
     *     room_id: int|null,
     *     start_time: Carbon|null,
     *     end_time: Carbon|null,
     *     occupancy_option: bool,
     *     approved_room_id: int|null,
     *     approved_start_time: Carbon|null,
     *     approved_end_time: Carbon|null
     * }
     */
    public function snapshot(Event $event): array
    {
        return [
            'room_id' => $this->nullableInt($event->getAttribute('room_id')),
            'start_time' => $this->nullableCarbon($event->getAttribute('start_time')),
            'end_time' => $this->nullableCarbon($event->getAttribute('end_time')),
            'occupancy_option' => (bool) $event->getAttribute('occupancy_option'),
            'approved_room_id' => $this->nullableInt($event->getAttribute('approved_room_id')),
            'approved_start_time' => $this->nullableCarbon($event->getAttribute('approved_start_time')),
            'approved_end_time' => $this->nullableCarbon($event->getAttribute('approved_end_time')),
        ];
    }

    /**
     * Setzt Anfrage-Status, bestätigte Belegung und anfragende Person am (noch nicht gespeicherten) Termin.
     *
     * @param array<string, mixed> $before Ergebnis von snapshot() vor der Änderung
     * @return bool true = die Änderung hat den Termin zu einer neuen Raumanfrage gemacht (Aufrufer benachrichtigt)
     */
    public function evaluate(?User $user, Event $event, array $before): bool
    {
        if ($user === null || $event->getAttribute('is_planning') || $before['occupancy_option']) {
            // Geplante Termine kennen keine Anfrage; eine offene Anfrage bleibt offen (die Raumadmins sehen
            // die Änderung an der bestehenden Meldung)
            return false;
        }

        $after = $this->snapshot($event);
        if ($after['room_id'] === null || !$this->timeOrRoomChanged($before, $after)) {
            return false;
        }

        if ($this->eventSettingsService->alwaysDirectBooking() || $this->mayBookDirectly($user, $after['room_id'])) {
            // Wer direkt buchen darf, bestätigt mit der Änderung den neuen Stand
            $this->setApproval($event, null);

            return false;
        }

        $approval = $this->approvalBefore($before);
        if ($approval !== null && $this->isWithin($after, $approval)) {
            $this->setApproval($event, $this->isSameSlot($after, $approval) ? null : $approval);

            return false;
        }

        $event->forceFill([
            'occupancy_option' => true,
            'declined_room_id' => null,
            'accepted' => false,
            'requested_by_id' => $user->getKey(),
        ]);
        $this->setApproval($event, $approval);

        return true;
    }

    /**
     * Wie evaluate(), für Wege, die den Termin schon gespeichert haben: speichert die Folgeänderung selbst.
     *
     * @param array<string, mixed> $before Ergebnis von snapshot() vor der Änderung
     * @return bool true = neue Raumanfrage (Aufrufer benachrichtigt)
     */
    public function applyAfterChange(?User $user, Event $event, array $before): bool
    {
        $becameRequest = $this->evaluate($user, $event, $before);
        if ($event->isDirty()) {
            $event->save();
        }

        return $becameRequest;
    }

    private function mayBookDirectly(User $user, int $roomId): bool
    {
        $room = Room::query()->find($roomId);

        // Dieselbe Regel wie beim Anlegen im Termin-Dialog (EventPolicy::book, Admins via Gate::before)
        return $room !== null && $user->can('book', [Event::class, $room, false, false]);
    }

    /**
     * @param array<string, mixed> $before
     * @return array{room_id: int, start_time: Carbon, end_time: Carbon}|null null = es gab keine Belegung
     *         (Termin ohne Raum, z. B. nach einer Absage)
     */
    private function approvalBefore(array $before): ?array
    {
        if (
            $before['approved_room_id'] !== null
            && $before['approved_start_time'] !== null
            && $before['approved_end_time'] !== null
        ) {
            return [
                'room_id' => $before['approved_room_id'],
                'start_time' => $before['approved_start_time'],
                'end_time' => $before['approved_end_time'],
            ];
        }

        if ($before['room_id'] === null || $before['start_time'] === null || $before['end_time'] === null) {
            return null;
        }

        return [
            'room_id' => $before['room_id'],
            'start_time' => $before['start_time'],
            'end_time' => $before['end_time'],
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    private function timeOrRoomChanged(array $before, array $after): bool
    {
        return $before['room_id'] !== $after['room_id']
            || !$this->sameMoment($before['start_time'], $after['start_time'])
            || !$this->sameMoment($before['end_time'], $after['end_time']);
    }

    /**
     * @param array<string, mixed> $slot
     * @param array{room_id: int, start_time: Carbon, end_time: Carbon} $approval
     */
    private function isWithin(array $slot, array $approval): bool
    {
        return $slot['room_id'] === $approval['room_id']
            && $slot['start_time'] !== null
            && $slot['end_time'] !== null
            && $this->toMinute($slot['start_time'])->gte($this->toMinute($approval['start_time']))
            && $this->toMinute($slot['end_time'])->lte($this->toMinute($approval['end_time']));
    }

    /**
     * @param array<string, mixed> $slot
     * @param array{room_id: int, start_time: Carbon, end_time: Carbon} $approval
     */
    private function isSameSlot(array $slot, array $approval): bool
    {
        return $slot['room_id'] === $approval['room_id']
            && $this->sameMoment($slot['start_time'], $approval['start_time'])
            && $this->sameMoment($slot['end_time'], $approval['end_time']);
    }

    /**
     * @param array{room_id: int, start_time: Carbon, end_time: Carbon}|null $approval
     */
    private function setApproval(Event $event, ?array $approval): void
    {
        $event->forceFill([
            'approved_room_id' => $approval['room_id'] ?? null,
            'approved_start_time' => $approval['start_time'] ?? null,
            'approved_end_time' => $approval['end_time'] ?? null,
        ]);
    }

    private function sameMoment(?Carbon $first, ?Carbon $second): bool
    {
        if ($first === null || $second === null) {
            return $first === $second;
        }

        return $this->toMinute($first)->equalTo($this->toMinute($second));
    }

    /**
     * Minutengenau: der Termin-Dialog speichert das Ende ganztägiger Termine als 23:59:00, Terminliste und KI als
     * 23:59:59 (endOfDay). Ohne Abschneiden wurde ein unveränderter ganztägiger Termin dort zur Raumanfrage.
     */
    private function toMinute(Carbon $moment): Carbon
    {
        return $moment->copy()->startOfMinute();
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function nullableCarbon(mixed $value): ?Carbon
    {
        return $value === null || $value === '' ? null : Carbon::parse($value);
    }
}
