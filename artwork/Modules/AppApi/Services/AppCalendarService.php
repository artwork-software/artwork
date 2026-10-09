<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Holidays\Services\HolidayService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AppCalendarService
{
    public function __construct(
        private readonly HolidayService $holidayService,
    ) {
    }

    /**
     * Room occupancy calendar in the app contract shape — one list entry per
     * calendar day (empty days included so the app can render a full week strip).
     * Unlike the desktop rooms×days grid this is day-keyed with the room inlined
     * per event; multi-day events are emitted on every day they span, matching
     * the web calendar's bucketing. The app validates this shape on the
     * device, so keys are load-bearing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDays(Carbon $startDate, Carbon $endDate): array
    {
        $eventsByDay = $this->bucketEventsByDay(
            $this->getEventsForRange($startDate, $endDate),
            $startDate,
            $endDate,
        );

        $holidaysByDate = $this->holidayService->getCalendarHolidaysByDate($startDate, $endDate);

        $days = [];
        $day = $startDate->copy()->startOfDay();
        while ($day->lte($endDate)) {
            $dayKey = $day->format('Y-m-d');
            $days[] = [
                'date' => $dayKey,
                'holidays' => $holidaysByDate->get($dayKey, collect())
                    ->map(static fn (CalendarHolidayDTO $holiday): array => [
                        'name' => $holiday->name,
                        'color' => $holiday->color,
                    ])
                    ->values()
                    ->all(),
                'events' => $eventsByDay[$dayKey] ?? [],
            ];
            $day->addDay();
        }

        return $days;
    }

    /**
     * Mirrors the web calendar's event selection (EventCalendarService): overlap
     * with the range, only rooms relevant for disposition, planned events excluded.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Event>
     */
    private function getEventsForRange(Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return Event::query()
            ->select([
                'id',
                'start_time',
                'end_time',
                'eventName',
                'description',
                'project_id',
                'event_type_id',
                'event_status_id',
                'allDay',
                'room_id',
            ])
            ->with([
                'room:id,name',
                'event_type:id,name,abbreviation,hex_code',
                'project:id,name',
                'eventStatus:id,color',
            ])
            ->where('is_planning', false)
            ->whereHas('room', static function (Builder $query): void {
                $query->where('relevant_for_disposition', true);
            })
            ->where(static function (Builder $query) use ($startDate, $endDate): void {
                $query->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate])
                    ->orWhere(static function (Builder $nested) use ($startDate, $endDate): void {
                        $nested->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                    });
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Event> $events
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function bucketEventsByDay(
        \Illuminate\Database\Eloquent\Collection $events,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $rangeStart = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->startOfDay();
        $buckets = [];

        foreach ($events as $event) {
            $mapped = $this->mapEvent($event);

            $loopStart = $event->start_time->copy()->startOfDay()->max($rangeStart);
            $loopEnd = $event->end_time->copy()->startOfDay()->min($rangeEnd);

            $day = $loopStart->copy();
            while ($day->lte($loopEnd)) {
                $buckets[$day->format('Y-m-d')][] = $mapped;
                $day->addDay();
            }
        }

        return $buckets;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->eventName,
            'description' => $event->description,
            'start' => $event->start_time->toIso8601String(),
            'end' => $event->end_time->toIso8601String(),
            'all_day' => (bool) $event->allDay,
            'event_type' => $this->mapEventType($event->event_type),
            'project' => $this->mapProject($event->project),
            'room' => $this->mapRoom($event->room),
            'status_color' => $event->eventStatus?->color,
        ];
    }

    /**
     * @return array{id: int, name: string, abbreviation: string, hex_code: string}|null
     */
    private function mapEventType(mixed $eventType): ?array
    {
        if (!$eventType instanceof EventType) {
            return null;
        }

        return [
            'id' => $eventType->id,
            'name' => $eventType->name,
            'abbreviation' => $eventType->abbreviation,
            'hex_code' => $eventType->hex_code,
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function mapProject(mixed $project): ?array
    {
        if (!$project instanceof Project) {
            return null;
        }

        return ['id' => $project->id, 'name' => $project->name];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function mapRoom(mixed $room): ?array
    {
        if (!$room instanceof Room) {
            return null;
        }

        return ['id' => $room->id, 'name' => $room->name];
    }
}
