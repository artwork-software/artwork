<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiCommonSlotsData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiTimeSlotData;
use Artwork\Modules\AiAssistant\Support\AiErrors;
use Artwork\Modules\AiAssistant\Support\AiShiftPlanningAccess;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gemeinsame freie Zeit mehrerer Personen (ai-scenarios-4 S14).
 *
 * Belegt ist eine Person durch ihre Schichten (Zeiten der Zuweisung, `resolvePivotInterval`) und
 * durch Abwesenheiten/Nicht-Verfuegbarkeiten (`AiPeopleService::absences()`, ganztaegig oder mit
 * Uhrzeit). Termine mit Teilnehmerliste kennt der Core nicht (Termine haben keine Personen) — sie
 * entfallen. Mit `room_id` zaehlen zusaetzlich die Termine im Raum (wie `free-slots`).
 * Die Luecken rechnet `FreeSlotFinder` ueber die vereinigte Belegung aller; ausgegeben werden die
 * ersten 10 Fenster chronologisch, ab jetzt (Vergangenes faellt weg).
 *
 * Recht: nur die eigene Person geht immer; fuer andere „Schichtplan sehen", „Schichten planen" oder
 * Admin, sonst 403 `no_shift_plan_access` (dieselbe Hoheit wie `GET /ai/absences`).
 */
class AiCommonSlotsService
{
    public const MAX_SLOTS = 10;

    public function __construct(
        private readonly AiPeopleService $people,
        private readonly FreeSlotFinder $finder,
    ) {
    }

    /**
     * @param list<array{type: string, id: int}> $refs
     */
    public function find(
        User $viewer,
        array $refs,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $durationMinutes,
        string $dayStart,
        string $dayEnd,
        ?int $roomId
    ): AiCommonSlotsData {
        $people = array_map(fn (array $ref) => $this->people->find($ref['type'], $ref['id']), $refs);

        $others = array_filter(
            $people,
            static fn ($person): bool => !($person instanceof User && $person->id === $viewer->id)
        );
        if (
            $others !== []
            && !AiShiftPlanningAccess::mayViewShiftPlan($viewer)
            && !AiShiftPlanningAccess::mayPlanShifts($viewer)
        ) {
            AiErrors::abort(AiErrors::NO_SHIFT_PLAN_ACCESS);
        }

        $room = null;
        if ($roomId !== null) {
            $room = Room::query()->visibleFor($viewer)->whereKey($roomId)->first();
            abort_if($room === null, Response::HTTP_NOT_FOUND);
        }

        $rangeStart = $from->startOfDay();
        $rangeEnd = $to->addDay()->startOfDay();
        $busy = [];

        foreach ($people as $person) {
            $busy = array_merge(
                $busy,
                $this->shiftIntervals($person, $rangeStart, $rangeEnd),
                $this->absenceIntervals($person, $from, $to)
            );
        }

        if ($room !== null) {
            $busy = array_merge($busy, $this->roomIntervals((int) $room->id, $rangeStart, $rangeEnd));
        }

        $slots = $this->finder->find(
            [0 => $busy],
            $from,
            $to,
            $durationMinutes / 60,
            [],
            ['from' => $dayStart, 'to' => $dayEnd],
            PHP_INT_MAX
        );

        $now = CarbonImmutable::now();
        $result = [];
        usort(
            $slots,
            static fn (array $a, array $b): int => $a['start']->getTimestamp() <=> $b['start']->getTimestamp()
        );
        $timezone = (string) config('app.timezone');

        foreach ($slots as $slot) {
            $start = $slot['start']->max($now);
            if ($start->diffInMinutes($slot['end'], false) < $durationMinutes) {
                continue;
            }

            $result[] = new AiTimeSlotData(
                $start->setTimezone($timezone)->toIso8601String(),
                $slot['end']->setTimezone($timezone)->toIso8601String(),
            );

            if (count($result) >= self::MAX_SLOTS) {
                break;
            }
        }

        return new AiCommonSlotsData(
            slots: $result,
            people: array_map(fn ($person) => $this->people->personData($person), $people),
            room: $room === null ? null : new AiRefData((int) $room->id, AiText::truncate($room->name)),
            duration_minutes: $durationMinutes,
            day_start: $dayStart,
            day_end: $dayEnd,
        );
    }

    /**
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function shiftIntervals(
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $rangeStart,
        CarbonImmutable $rangeEnd
    ): array {
        $intervals = [];
        $pivots = ShiftWorker::query()
            ->where('employable_type', $person->getMorphClass())
            ->where('employable_id', $person->getKey())
            ->whereHas('shift', static fn (Builder $shift) => $shift
                ->whereDate('start_date', '<=', $rangeEnd->toDateString())
                ->whereRaw('COALESCE(end_date, start_date) >= ?', [$rangeStart->subDay()->toDateString()]))
            ->with('shift')
            ->get();

        foreach ($pivots as $pivot) {
            if ($pivot->shift === null) {
                continue;
            }

            $interval = ShiftAssignmentPreflightService::resolvePivotInterval($pivot, $pivot->shift);
            if ($interval !== null) {
                $intervals[] = [
                    'start' => CarbonImmutable::instance($interval[0]),
                    'end' => CarbonImmutable::instance($interval[1]),
                ];
            }
        }

        return $intervals;
    }

    /**
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function absenceIntervals(
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): array {
        $intervals = [];

        foreach ($this->people->absences($person, $from, $to) as $absence) {
            $end = CarbonImmutable::parse($absence->to);
            // Ganztaegige Zeitraeume enden bei 23:59 — bis Mitternacht belegt.
            if ($end->format('H:i') === '23:59') {
                $end = $end->addDay()->startOfDay();
            }

            $intervals[] = ['start' => CarbonImmutable::parse($absence->from), 'end' => $end];
        }

        return $intervals;
    }

    /**
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function roomIntervals(int $roomId, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        return Event::query()
            ->where('room_id', $roomId)
            ->where('start_time', '<', $rangeEnd)
            ->where('end_time', '>', $rangeStart)
            ->get(['id', 'start_time', 'end_time'])
            ->filter(static fn (Event $event): bool => $event->start_time !== null && $event->end_time !== null)
            ->map(static fn (Event $event): array => [
                'start' => CarbonImmutable::parse($event->start_time),
                'end' => CarbonImmutable::parse($event->end_time),
            ])
            ->values()
            ->all();
    }
}
