<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiEventTypeMinutesData;
use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomUtilizationData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomUtilizationListData;
use Artwork\Modules\AiAssistant\DTOs\AiUtilizationWindowData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Raumauslastung (ai-scenarios-4 S17).
 *
 * Je Raum und Tag werden die Termine auf das Bezugsfenster beschnitten und vereinigt
 * (Ueberlappungen zaehlen einmal). Raeume haben im Core keine Oeffnungszeiten, das Fenster ist
 * deshalb immer 08:00–24:00 (`source = default`). Gezaehlt werden nicht geloeschte Termine ohne
 * Planungstermine (wie S6). Terminarten werden je Art vereinigt (Top 3 nach Minuten); keine
 * Termindetails.
 */
class AiRoomUtilizationService
{
    public const WINDOW_START = '08:00';

    public const WINDOW_END = '24:00';

    public const WINDOW_SOURCE = 'default';

    private const TOP_TYPES = 3;

    public function utilization(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $roomId,
        ?int $limit
    ): AiRoomUtilizationListData {
        $rooms = Room::query()
            ->visibleFor($user)
            ->when($roomId !== null, static fn ($query) => $query->whereKey($roomId))
            ->orderBy('name')
            ->get();
        abort_if($roomId !== null && $rooms->isEmpty(), Response::HTTP_NOT_FOUND);

        $max = (int) config('ai.list_limit');
        $limit = $limit === null ? $max : min(max(1, $limit), $max);
        $days = $this->days($from, $to);
        $windowMinutes = $this->minutesOfDay(self::WINDOW_END) - $this->minutesOfDay(self::WINDOW_START);

        $events = Event::query()
            ->whereIn('room_id', $rooms->modelKeys())
            ->where('is_planning', false)
            ->where('start_time', '<', $to->addDay()->startOfDay())
            ->where('end_time', '>', $from->startOfDay())
            ->with('event_type')
            ->get(['id', 'room_id', 'start_time', 'end_time', 'event_type_id'])
            ->groupBy('room_id');

        $data = [];
        foreach ($rooms as $room) {
            $data[] = $this->roomEntry($room, $events->get($room->id, collect())->all(), $days, $windowMinutes);
        }

        usort(
            $data,
            static fn (AiRoomUtilizationData $a, AiRoomUtilizationData $b): int =>
                [$b->utilization_percent, $a->room->name] <=> [$a->utilization_percent, $b->room->name]
        );

        return new AiRoomUtilizationListData(
            period: AiPeriodData::fromDates($from, $to),
            window: new AiUtilizationWindowData(self::WINDOW_START, self::WINDOW_END, self::WINDOW_SOURCE),
            data: array_slice($data, 0, $limit),
            total: count($data),
            limit: $limit,
            truncated: count($data) > $limit,
        );
    }

    /**
     * @param list<Event> $events
     * @param list<CarbonImmutable> $days
     */
    private function roomEntry(Room $room, array $events, array $days, int $windowMinutes): AiRoomUtilizationData
    {
        $booked = 0;
        $daysBooked = 0;
        $counted = [];
        $byType = [];
        $typeNames = [];

        foreach ($days as $day) {
            $windowStart = $this->atTime($day, self::WINDOW_START);
            $windowEnd = $this->atTime($day, self::WINDOW_END);
            $all = [];
            $perType = [];

            foreach ($events as $event) {
                $start = CarbonImmutable::parse($event->start_time)->max($windowStart);
                $end = CarbonImmutable::parse($event->end_time)->min($windowEnd);

                if (!$end->greaterThan($start)) {
                    continue;
                }

                $counted[$event->id] = true;
                $all[] = [$start->getTimestamp(), $end->getTimestamp()];
                $typeId = (int) $event->event_type_id;
                $typeNames[$typeId] = $event->event_type?->name;
                $perType[$typeId][] = [$start->getTimestamp(), $end->getTimestamp()];
            }

            $dayMinutes = $this->unionMinutes($all);
            $booked += $dayMinutes;
            $daysBooked += $dayMinutes > 0 ? 1 : 0;

            foreach ($perType as $typeId => $intervals) {
                $byType[$typeId] = ($byType[$typeId] ?? 0) + $this->unionMinutes($intervals);
            }
        }

        arsort($byType);
        $available = $windowMinutes * count($days);

        return new AiRoomUtilizationData(
            room: new AiRefData((int) $room->id, AiText::truncate($room->name)),
            booked_minutes: $booked,
            available_minutes: $available,
            utilization_percent: $available === 0 ? 0.0 : round($booked / $available * 100, 1),
            events: count($counted),
            days_booked: $daysBooked,
            top_event_types: array_map(
                static fn (int $typeId, int $minutes): AiEventTypeMinutesData => new AiEventTypeMinutesData(
                    AiText::truncate($typeNames[$typeId] ?? null),
                    $minutes
                ),
                array_keys(array_slice($byType, 0, self::TOP_TYPES, true)),
                array_values(array_slice($byType, 0, self::TOP_TYPES, true))
            ),
        );
    }

    /**
     * Minuten der Vereinigung von Intervallen `[start, end]` (Sekunden).
     *
     * @param list<array{0: int, 1: int}> $intervals
     */
    private function unionMinutes(array $intervals): int
    {
        sort($intervals);
        $seconds = 0;
        $current = null;

        foreach ($intervals as [$start, $end]) {
            if ($current !== null && $start <= $current[1]) {
                $current[1] = max($current[1], $end);
                continue;
            }

            if ($current !== null) {
                $seconds += $current[1] - $current[0];
            }

            $current = [$start, $end];
        }

        if ($current !== null) {
            $seconds += $current[1] - $current[0];
        }

        return intdiv($seconds, 60);
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function days(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = [];
        for ($day = $from->startOfDay(); !$day->greaterThan($to->startOfDay()); $day = $day->addDay()) {
            $days[] = $day;
        }

        return $days;
    }

    private function minutesOfDay(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }

    /**
     * Uhrzeit am Kalendertag (Wanduhr, auch an Tagen mit Zeitumstellung); `24:00` = Mitternacht danach.
     */
    private function atTime(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour >= 24 ? $day->addDay()->startOfDay() : $day->setTime($hour, $minute);
    }
}
