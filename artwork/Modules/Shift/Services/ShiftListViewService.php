<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Models\UserShiftListViewSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

readonly class ShiftListViewService
{
    public function getGroupedShifts(
        Carbon $startDate,
        Carbon $endDate,
        UserShiftListViewSettings $settings,
        ?UserFilter $userFilter = null
    ): array {
        $query = Shift::query()
            ->with([
                'craft:id,name,abbreviation,color,position',
                'craft.qualifications:id,name',
                'room:id,name,position',
                'project:id,name',
                'event:id,event_type_id,project_id',
                'event.project:id,name',
                'users:id,first_name,last_name',
                'freelancer:id,first_name,last_name',
                'serviceProvider:id,provider_name',
                'shiftsQualifications:id,shift_id,shift_qualification_id,value',
                'shiftGroup:id,name',
                'globalQualifications',
            ])
            ->eventStartDayAndEventEndDayBetween($startDate, $endDate);

        if ($userFilter) {
            if (!empty($userFilter->craft_ids)) {
                $query->whereIn('craft_id', $userFilter->craft_ids);
            }

            if (!empty($userFilter->room_ids)) {
                $query->whereIn('room_id', $userFilter->room_ids);
            }

            if (!empty($userFilter->event_type_ids)) {
                $query->whereHas('event', function ($q) use ($userFilter) {
                    $q->whereIn('event_type_id', $userFilter->event_type_ids);
                });
            }

            if (!empty($userFilter->area_ids)) {
                $query->whereHas('room', function ($q) use ($userFilter) {
                    $q->whereIn('area_id', $userFilter->area_ids);
                });
            }

            if (!empty($userFilter->room_category_ids)) {
                $query->whereHas('room.categories', function ($q) use ($userFilter) {
                    $q->whereIn('room_categories.id', $userFilter->room_category_ids);
                });
            }

            if (!empty($userFilter->room_attribute_ids)) {
                $query->whereHas('room.attributes', function ($q) use ($userFilter) {
                    $q->whereIn('room_attributes.id', $userFilter->room_attribute_ids);
                });
            }

            if (!empty($userFilter->event_property_ids)) {
                $query->whereHas('event.eventProperties', function ($q) use ($userFilter) {
                    $q->whereIn('event_properties.id', $userFilter->event_property_ids);
                });
            }
        }

        $shifts = $query->get();

        if (!$settings->show_fully_staffed_shifts) {
            $shifts = $shifts->filter(function (Shift $shift) {
                $assignedCount = $shift->users->count()
                    + $shift->freelancer->count()
                    + $shift->serviceProvider->count();
                $requiredCount = $shift->shiftsQualifications->sum('value');

                return $assignedCount < $requiredCount;
            });
        }

        $shifts = $shifts->sortBy([
            ['start_date', 'asc'],
            fn (Shift $a, Shift $b) => ($a->room->position ?? 0) <=> ($b->room->position ?? 0),
            fn (Shift $a, Shift $b) => ($a->craft->position ?? 0) <=> ($b->craft->position ?? 0),
            ['start', 'asc'],
        ]);

        // Group by day, then by room — using arrays (not objects with numeric keys)
        // to preserve room.position ordering in JavaScript
        $dayMap = [];
        foreach ($shifts as $shift) {
            $day = Carbon::parse($shift->start_date)->format('Y-m-d');
            $roomId = $shift->room_id ?? 0;

            if (!isset($dayMap[$day])) {
                $dayMap[$day] = [];
            }
            if (!isset($dayMap[$day][$roomId])) {
                $dayMap[$day][$roomId] = [
                    'room_id' => $roomId,
                    'room' => [
                        'id' => $roomId,
                        'name' => $shift->room?->name,
                        'position' => $shift->room?->position ?? 0,
                    ],
                    'shifts' => [],
                    'events' => [],
                ];
            }
            $dayMap[$day][$roomId]['shifts'][] = $this->serializeShift($shift);
        }

        if ($settings->show_appointments) {
            $events = $this->getEventsForRange($startDate, $endDate, $userFilter);

            foreach ($events as $event) {
                $serializedEvent = $this->serializeListViewEvent($event);
                // An event can span multiple days; emit it on each day in range
                $eventStart = $event->start_time->copy()->startOfDay();
                $eventEnd = $event->end_time->copy()->startOfDay();
                $rangeStart = $startDate->copy()->startOfDay();
                $rangeEnd = $endDate->copy()->startOfDay();

                $loopStart = $eventStart->lt($rangeStart) ? $rangeStart : $eventStart;
                $loopEnd = $eventEnd->gt($rangeEnd) ? $rangeEnd : $eventEnd;

                for ($day = $loopStart->copy(); $day->lte($loopEnd); $day->addDay()) {
                    $dayKey = $day->format('Y-m-d');
                    $roomId = $event->room_id ?? 0;

                    if (!isset($dayMap[$dayKey])) {
                        $dayMap[$dayKey] = [];
                    }
                    if (!isset($dayMap[$dayKey][$roomId])) {
                        $dayMap[$dayKey][$roomId] = [
                            'room_id' => $roomId,
                            'room' => [
                                'id' => $roomId,
                                'name' => $event->room?->name,
                                'position' => $event->room?->position ?? 0,
                            ],
                            'shifts' => [],
                            'events' => [],
                        ];
                    }
                    $dayMap[$dayKey][$roomId]['events'][] = $serializedEvent;
                }
            }
        }

        ksort($dayMap);

        // Sort rooms within each day by room.position; sort events within each room by start_time
        foreach ($dayMap as $day => $rooms) {
            uasort($rooms, function ($a, $b) {
                return ($a['room']['position'] ?? 0) <=> ($b['room']['position'] ?? 0);
            });
            foreach ($rooms as $roomId => $roomData) {
                if (!empty($roomData['events'])) {
                    usort($rooms[$roomId]['events'], function (array $a, array $b) {
                        return $a['start_time'] <=> $b['start_time'];
                    });
                }
            }
            $dayMap[$day] = $rooms;
        }

        // Load holidays for the date range
        $holidaysByDate = $this->getHolidaysForRange($startDate, $endDate)
            ->groupBy(fn (CalendarHolidayDTO $h) => $h->date);

        // Convert to sequential arrays so JS preserves insertion order
        $result = [];
        foreach ($dayMap as $day => $rooms) {
            $result[] = [
                'day' => $day,
                'rooms' => array_values($rooms),
                'holidays' => ($holidaysByDate->get($day, collect()))->values()->toArray(),
            ];
        }

        return $result;
    }

    private function serializeShift(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'start' => (string) $shift->start,
            'end' => (string) $shift->end,
            'start_date' => (string) $shift->start_date,
            'end_date' => (string) $shift->end_date,
            'break_minutes' => $shift->break_minutes,
            'description' => $shift->description,
            'craft_id' => $shift->craft_id,
            'room_id' => $shift->room_id,
            'event_id' => $shift->event_id,
            'is_committed' => $shift->is_committed,
            'shift_group_id' => $shift->shift_group_id,
            'craft' => $shift->craft ? [
                'id' => $shift->craft->id,
                'name' => $shift->craft->name,
                'abbreviation' => $shift->craft->abbreviation,
                'color' => $shift->craft->color,
            ] : null,
            'shift_group' => $shift->shiftGroup ? [
                'id' => $shift->shiftGroup->id,
                'name' => $shift->shiftGroup->name,
            ] : null,
            'project' => $shift->project ? [
                'id' => $shift->project->id,
                'name' => $shift->project->name,
            ] : null,
            'event' => $shift->event ? [
                'id' => $shift->event->id,
                'project' => $shift->event->project ? [
                    'id' => $shift->event->project->id,
                    'name' => $shift->event->project->name,
                ] : null,
            ] : null,
            'workers' => [
                ...$this->serializeListViewWorkers($shift->users, 'user'),
                ...$this->serializeListViewWorkers($shift->freelancer, 'freelancer'),
                ...$this->serializeListViewWorkers($shift->serviceProvider, 'service_provider'),
            ],
            'shifts_qualifications' => $shift->shiftsQualifications->map(fn ($sq) => [
                'id' => $sq->id,
                'shift_id' => $sq->shift_id,
                'shift_qualification_id' => $sq->shift_qualification_id,
                'value' => $sq->value,
            ])->values()->all(),
            'globalQualifications' => $shift->globalQualifications->map(fn ($gq) => [
                'id' => $gq->id,
                'pivot' => ['quantity' => $gq->pivot->quantity ?? 0],
            ])->values()->all(),
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection $workers
     */
    private function serializeListViewWorkers($workers, string $type): array
    {
        if ($workers === null || $workers->isEmpty()) {
            return [];
        }

        return $workers->map(function ($worker) use ($type) {
            $data = [
                'id' => $worker->id,
                'type' => $type,
                'pivot' => $worker->pivot ? [
                    'shift_qualification_id' => $worker->pivot->shift_qualification_id ?? null,
                ] : null,
            ];

            if ($type === 'service_provider') {
                $data['provider_name'] = $worker->provider_name;
                $data['name'] = $worker->provider_name;
            } else {
                $data['first_name'] = $worker->first_name;
                $data['last_name'] = $worker->last_name;
                $data['name'] = trim($worker->first_name . ' ' . $worker->last_name);
            }

            return $data;
        })->values()->all();
    }

    private function serializeListViewEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'eventName' => $event->eventName,
            'allDay' => (bool) $event->allDay,
            'start_time' => (string) $event->start_time,
            'end_time' => (string) $event->end_time,
            'event_type' => $event->event_type ? [
                'id' => $event->event_type->id,
                'name' => $event->event_type->name,
                'abbreviation' => $event->event_type->abbreviation,
                'hex_code' => $event->event_type->hex_code,
            ] : null,
            'project' => $event->project ? [
                'id' => $event->project->id,
                'name' => $event->project->name,
            ] : null,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Event>
     */
    private function getEventsForRange(
        Carbon $startDate,
        Carbon $endDate,
        ?UserFilter $userFilter
    ): \Illuminate\Database\Eloquent\Collection {
        // Note: only the relations actually rendered in the appointments column are eager-loaded.
        // event_type → abbreviation+color, project → name, room → position for sort.
        $query = Event::query()
            ->with([
                'room:id,name,position',
                'event_type:id,name,abbreviation,hex_code',
                'project:id,name',
            ])
            ->where(function (Builder $q) use ($startDate, $endDate): void {
                $q->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate])
                    ->orWhere(function (Builder $nested) use ($startDate, $endDate): void {
                        $nested->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                    });
            });

        if ($userFilter) {
            if (!empty($userFilter->room_ids)) {
                $query->whereIn('room_id', $userFilter->room_ids);
            }

            if (!empty($userFilter->event_type_ids)) {
                $query->whereIn('event_type_id', $userFilter->event_type_ids);
            }

            if (!empty($userFilter->area_ids)) {
                $query->whereHas('room', function ($q) use ($userFilter) {
                    $q->whereIn('area_id', $userFilter->area_ids);
                });
            }

            if (!empty($userFilter->room_category_ids)) {
                $query->whereHas('room.categories', function ($q) use ($userFilter) {
                    $q->whereIn('room_categories.id', $userFilter->room_category_ids);
                });
            }

            if (!empty($userFilter->room_attribute_ids)) {
                $query->whereHas('room.attributes', function ($q) use ($userFilter) {
                    $q->whereIn('room_attributes.id', $userFilter->room_attribute_ids);
                });
            }

            if (!empty($userFilter->event_property_ids)) {
                $query->whereHas('eventProperties', function ($q) use ($userFilter) {
                    $q->whereIn('event_properties.id', $userFilter->event_property_ids);
                });
            }
        }

        return $query->get();
    }

    private function getHolidaysForRange(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        return Holiday::select(['id', 'name', 'date', 'end_date', 'color', 'yearly'])
            ->where(function (Builder $q) use ($start, $end): void {
                $q->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function (Builder $nested) use ($start, $end): void {
                        $nested->where('date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    })
                    ->orWhere(function (Builder $nested) use ($start, $end): void {
                        $nested->where('yearly', true)
                            ->whereBetween(\DB::raw('DATE_FORMAT(date, "%m-%d")'), [$start->format('m-d'), $end->format('m-d')]);
                    });
            })
            ->with(['subdivisions' => fn ($q) => $q->select('name')])
            ->get()
            ->map(fn ($holiday) => new CalendarHolidayDTO(
                name: $holiday->name,
                date: $holiday->date->toDateString(),
                end_date: $holiday->end_date->toDateString(),
                color: $holiday->color,
                subdivisions: $holiday->subdivisions->pluck('name')->toArray(),
            ));
    }
}
