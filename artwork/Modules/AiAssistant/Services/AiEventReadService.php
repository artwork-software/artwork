<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiDayWindowData;
use Artwork\Modules\AiAssistant\DTOs\AiEventData;
use Artwork\Modules\AiAssistant\DTOs\AiEventListData;
use Artwork\Modules\AiAssistant\DTOs\AiFreeSlotData;
use Artwork\Modules\AiAssistant\DTOs\AiFreeSlotListData;
use Artwork\Modules\AiAssistant\DTOs\AiOccupancyDayData;
use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomOccupancyData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomOccupancyListData;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Termine, Raumbelegung und freie Zeitfenster fuer artwork-ai
 * (core-api-events-rooms; Konzept B1 §3–§5).
 *
 * **Zwei verschiedene Datenquellen, mit Absicht:**
 * - Terminliste: `Event::visibleFor($user)` — Inhalte nur, soweit die Person sie sehen darf.
 * - Belegung und freie Slots: **alle** Termine der Raeume. Ein belegter Raum ist ein Faktum;
 *   waeren unsichtbare Termine hier ausgeblendet, wuerde der Assistent einen belegten Raum als
 *   frei vorschlagen. Es gehen dabei nur Zeiten raus, nie Namen, Projekte oder Typen (B1 §5).
 */
class AiEventReadService
{
    public function __construct(private readonly FreeSlotFinder $finder)
    {
    }

    /**
     * @param array{room_ids?: list<int>, project_id?: int|null, type_id?: int|null, limit?: int|null} $filters
     */
    public function search(User $user, CarbonImmutable $from, CarbonImmutable $to, array $filters): AiEventListData
    {
        $limit = $this->limit($filters['limit'] ?? null);

        $query = Event::query()
            ->visibleFor($user)
            ->with(['room', 'project', 'event_type', 'eventStatus'])
            ->where('start_time', '<=', $to)
            ->where('end_time', '>=', $from)
            ->when(
                ($filters['room_ids'] ?? []) !== [],
                fn (Builder $builder) => $builder->whereIn('room_id', $filters['room_ids'])
            )
            ->when(
                ($filters['project_id'] ?? null) !== null,
                fn (Builder $builder) => $builder->where('project_id', $filters['project_id'])
            )
            ->when(
                ($filters['type_id'] ?? null) !== null,
                fn (Builder $builder) => $builder->where('event_type_id', $filters['type_id'])
            );

        $total = (clone $query)->count();
        $events = $query->orderBy('start_time')->limit($limit)->get();

        return new AiEventListData(
            data: $events->map(AiEventData::fromModel(...))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * @param list<int> $roomIds leer = alle sichtbaren Raeume
     */
    public function occupancy(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        array $roomIds
    ): AiRoomOccupancyListData {
        $rooms = $this->rooms($user, $roomIds);
        $window = AiDayWindowData::fromConfig();
        $occupancy = $this->occupancyByRoom($rooms->modelKeys(), $from, $to);

        $data = $rooms->map(fn (Room $room): AiRoomOccupancyData => new AiRoomOccupancyData(
            room: new AiRefData($room->id, $room->name),
            days: $this->daysFor($occupancy[$room->id] ?? [], $from, $to, $window),
        ))->all();

        return new AiRoomOccupancyListData(
            data: $data,
            period: AiPeriodData::fromDates($from, $to),
            day_window: $window,
        );
    }

    /**
     * @param list<int> $roomIds
     * @param list<int> $weekdays
     * @param array{from: string, to: string} $dayWindow
     */
    public function freeSlots(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        array $roomIds,
        float $minHours,
        array $weekdays,
        array $dayWindow
    ): AiFreeSlotListData {
        $rooms = $this->rooms($user, $roomIds);
        $occupancy = $this->occupancyByRoom($rooms->modelKeys(), $from, $to);

        // Auch leere Raeume brauchen einen Eintrag, sonst tauchen sie in der Suche nie auf.
        foreach ($rooms->modelKeys() as $roomId) {
            $occupancy[$roomId] ??= [];
        }

        $slots = $this->finder->find($occupancy, $from, $to, $minHours, $weekdays, $dayWindow);
        $timezone = (string) config('app.timezone');

        return new AiFreeSlotListData(
            data: array_map(
                static fn (array $slot): AiFreeSlotData => new AiFreeSlotData(
                    room_id: $slot['room_id'],
                    start: $slot['start']->setTimezone($timezone)->toIso8601String(),
                    end: $slot['end']->setTimezone($timezone)->toIso8601String(),
                    hours: $slot['hours'],
                ),
                $slots
            ),
            period: AiPeriodData::fromDates($from, $to),
            day_window: new AiDayWindowData($dayWindow['from'], $dayWindow['to']),
            min_hours: $minHours,
        );
    }

    /**
     * @param list<int> $roomIds
     * @return Collection<int, Room>
     */
    private function rooms(User $user, array $roomIds): Collection
    {
        return Room::query()
            ->visibleFor($user)
            ->when($roomIds !== [], fn (Builder $builder) => $builder->whereIn('id', $roomIds))
            ->orderBy('name')
            ->get();
    }

    /**
     * Belegungen je Raum — bewusst ohne `visibleFor`, siehe Klassenkommentar.
     *
     * @param list<int> $roomIds
     * @return array<int, list<array{start: CarbonImmutable, end: CarbonImmutable}>>
     */
    private function occupancyByRoom(array $roomIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        if ($roomIds === []) {
            return [];
        }

        $rows = Event::query()
            ->whereIn('room_id', $roomIds)
            ->where('start_time', '<=', $to)
            ->where('end_time', '>=', $from)
            ->orderBy('start_time')
            ->get(['id', 'room_id', 'start_time', 'end_time']);

        $occupancy = [];

        foreach ($rows as $row) {
            if ($row->start_time === null || $row->end_time === null) {
                continue;
            }

            $occupancy[(int) $row->room_id][] = [
                'start' => CarbonImmutable::parse($row->start_time),
                'end' => CarbonImmutable::parse($row->end_time),
            ];
        }

        return $occupancy;
    }

    /**
     * @param list<array{start: CarbonImmutable, end: CarbonImmutable}> $occupancy
     * @return list<AiOccupancyDayData>
     */
    private function daysFor(
        array $occupancy,
        CarbonImmutable $from,
        CarbonImmutable $to,
        AiDayWindowData $window
    ): array {
        $days = [];
        $day = $from->startOfDay();
        $last = $to->startOfDay();

        while (!$day->greaterThan($last)) {
            $windowStart = $this->atTime($day, $window->from);
            $windowEnd = $this->atTime($day, $window->to);
            $windowHours = max(0.0, ($windowEnd->getTimestamp() - $windowStart->getTimestamp()) / 3600);

            $busySeconds = 0;
            $events = 0;

            foreach ($this->mergedWithinWindow($occupancy, $windowStart, $windowEnd) as $entry) {
                $busySeconds += $entry['end']->getTimestamp() - $entry['start']->getTimestamp();
            }

            foreach ($occupancy as $entry) {
                if ($entry['start']->lessThan($windowEnd) && $entry['end']->greaterThan($windowStart)) {
                    $events++;
                }
            }

            $busyHours = round($busySeconds / 3600, 2);

            $days[] = new AiOccupancyDayData(
                date: $day->toDateString(),
                busy_hours: $busyHours,
                free_hours: round(max(0.0, $windowHours - $busyHours), 2),
                event_count: $events,
            );

            $day = $day->addDay();
        }

        return $days;
    }

    /**
     * Belegungen auf das Tagesfenster beschneiden und Ueberschneidungen zusammenfassen, damit
     * zwei parallele Termine nicht doppelt zaehlen.
     *
     * @param list<array{start: CarbonImmutable, end: CarbonImmutable}> $occupancy
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function mergedWithinWindow(
        array $occupancy,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd
    ): array {
        $clipped = [];

        foreach ($occupancy as $entry) {
            $start = $entry['start']->greaterThan($windowStart) ? $entry['start'] : $windowStart;
            $end = $entry['end']->lessThan($windowEnd) ? $entry['end'] : $windowEnd;

            if ($end->greaterThan($start)) {
                $clipped[] = ['start' => $start, 'end' => $end];
            }
        }

        usort(
            $clipped,
            static fn (array $a, array $b): int => $a['start']->getTimestamp() <=> $b['start']->getTimestamp()
        );

        $merged = [];

        foreach ($clipped as $entry) {
            $lastKey = $merged === [] ? null : array_key_last($merged);

            if ($lastKey !== null && !$entry['start']->greaterThan($merged[$lastKey]['end'])) {
                if ($entry['end']->greaterThan($merged[$lastKey]['end'])) {
                    $merged[$lastKey]['end'] = $entry['end'];
                }

                continue;
            }

            $merged[] = $entry;
        }

        return $merged;
    }

    private function atTime(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return $day->setTime($hour, $minute);
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }
}
