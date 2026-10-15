<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Holidays\Services\HolidayService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Serializers\ShiftListViewSerializer;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Models\UserShiftListViewSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

readonly class ShiftListViewService
{
    public function __construct(
        private ShiftListViewSerializer $serializer,
        private HolidayService $holidayService,
    ) {
    }
    /**
     * Der persistierte Zeitraum-Filter der Listenansicht, beim ersten Aufruf mit dem laufenden
     * Monat angelegt — Web und App lesen dieselbe Zeile.
     */
    public function filterFor(User $user): UserFilter
    {
        return $user->userFilters()->firstOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_LIST_VIEW_FILTER->value],
            [
                'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            ],
        );
    }

    public function settingsFor(User $user): UserShiftListViewSettings
    {
        return $user->shift_list_view_settings ?? $user->shift_list_view_settings()->create();
    }

    public function getGroupedShifts(
        Carbon $startDate,
        Carbon $endDate,
        UserShiftListViewSettings $settings,
        ?UserFilter $userFilter = null
    ): array {
        // Abwesenheiten im Zeitraum für das is_unavailable-Flag (ohne N+1)
        $vacationsScope = fn ($q) => $q
            ->without(['series', 'conflicts'])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

        $query = Shift::query()
            ->with([
                'craft:id,name,abbreviation,color,position',
                'craft.qualifications:id,name',
                'room:id,name,position',
                'project:id,name',
                'event:id,event_type_id,project_id',
                'event.project:id,name',
                'users:id,first_name,last_name',
                'users.vacations' => $vacationsScope,
                'users.globalQualifications:id',
                'freelancer:id,first_name,last_name',
                'freelancer.vacations' => $vacationsScope,
                'freelancer.globalQualifications:id',
                'serviceProvider:id,provider_name',
                'serviceProvider.vacations' => $vacationsScope,
                'serviceProvider.globalQualifications:id',
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
                $query->whereHas('event', function ($q) use ($userFilter): void {
                    $q->whereIn('event_type_id', $userFilter->event_type_ids);
                });
            }

            if (!empty($userFilter->area_ids)) {
                $query->whereHas('room', function ($q) use ($userFilter): void {
                    $q->whereIn('area_id', $userFilter->area_ids);
                });
            }

            if (!empty($userFilter->room_category_ids)) {
                $query->whereHas('room.categories', function ($q) use ($userFilter): void {
                    $q->whereIn('room_categories.id', $userFilter->room_category_ids);
                });
            }

            if (!empty($userFilter->room_attribute_ids)) {
                $query->whereHas('room.attributes', function ($q) use ($userFilter): void {
                    $q->whereIn('room_attributes.id', $userFilter->room_attribute_ids);
                });
            }

            if (!empty($userFilter->event_property_ids)) {
                $query->whereHas('event.eventProperties', function ($q) use ($userFilter): void {
                    $q->whereIn('event_properties.id', $userFilter->event_property_ids);
                });
            }
        }

        $shifts = $query->get();

        // Verfügbarkeitsstatus je Schicht × Person nur einmal ermitteln: der Filter unten und die
        // Serialisierung fragen ihn beide ab (je Status mehrere Carbon-Operationen)
        $unavailableStatuses = [];
        $unavailableStatusFor = static function (Shift $shift, $worker) use (&$unavailableStatuses): ?string {
            $key = spl_object_id($shift) . ':' . spl_object_id($worker);
            if (!array_key_exists($key, $unavailableStatuses)) {
                $unavailableStatuses[$key] = ShiftWorkerAvailability::getWorkerUnavailableStatus($shift, $worker);
            }

            return $unavailableStatuses[$key];
        };

        if (!$settings->show_fully_staffed_shifts) {
            $shifts = $shifts->filter(function (Shift $shift) use ($unavailableStatusFor) {
                $assignedCount = $shift->users->count()
                    + $shift->freelancer->count()
                    + $shift->serviceProvider->count();
                $requiredCount = $shift->shiftsQualifications->sum('value');

                // Always show shifts that have no staffing requirements set (0/0),
                // since they are not "fully staffed" but simply unconfigured.
                if ($requiredCount === 0) {
                    return true;
                }

                if ($assignedCount < $requiredCount) {
                    return true;
                }

                // Eine Schicht mit eingeplanter, aber nicht (mehr) verfügbarer Person
                // zählt nicht als voll besetzt — es besteht weiter Belegungsbedarf.
                foreach ([$shift->users, $shift->freelancer, $shift->serviceProvider] as $workers) {
                    foreach ($workers as $worker) {
                        if ($unavailableStatusFor($shift, $worker) !== null) {
                            return true;
                        }
                    }
                }

                return false;
            });
        }

        $shifts = $this->sortForListView($shifts);

        // Group by day, then by room — using arrays (not objects with numeric keys)
        // to preserve room.position ordering in JavaScript
        $dayMap = [];
        foreach ($shifts as $shift) {
            $day = $this->startDay($shift);
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
            $dayMap[$day][$roomId]['shifts'][] = $this->serializer->serializeShift($shift, $unavailableStatusFor);
        }

        if ($settings->show_appointments) {
            $events = $this->getEventsForRange($startDate, $endDate, $userFilter);

            foreach ($events as $event) {
                $serializedEvent = $this->serializer->serializeListViewEvent($event);
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
        $holidaysByDate = $this->holidayService->getCalendarHolidaysByDate($startDate, $endDate);

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

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Event>
     */
    private function getEventsForRange(
        Carbon $startDate,
        Carbon $endDate,
        ?UserFilter $userFilter
    ): \Illuminate\Database\Eloquent\Collection {
        // start_time/end_time sind Datetime-Spalten, der Filter liefert reine Datumswerte
        // (Mitternacht) — ohne endOfDay fehlen alle Termine des letzten Zeitraumtags.
        $rangeStart = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->endOfDay();

        // Note: only the relations actually rendered in the appointments column are eager-loaded.
        // event_type → abbreviation+color, project → name, room → position for sort.
        $query = Event::query()
            ->with([
                'room:id,name,position',
                'event_type:id,name,abbreviation,hex_code',
                'project:id,name',
            ])
            ->where(function (Builder $q) use ($rangeStart, $rangeEnd): void {
                $q->whereBetween('start_time', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('end_time', [$rangeStart, $rangeEnd])
                    ->orWhere(function (Builder $nested) use ($rangeStart, $rangeEnd): void {
                        $nested->where('start_time', '<=', $rangeStart)
                            ->where('end_time', '>=', $rangeEnd);
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
                $query->whereHas('room', function ($q) use ($userFilter): void {
                    $q->whereIn('area_id', $userFilter->area_ids);
                });
            }

            if (!empty($userFilter->room_category_ids)) {
                $query->whereHas('room.categories', function ($q) use ($userFilter): void {
                    $q->whereIn('room_categories.id', $userFilter->room_category_ids);
                });
            }

            if (!empty($userFilter->room_attribute_ids)) {
                $query->whereHas('room.attributes', function ($q) use ($userFilter): void {
                    $q->whereIn('room_attributes.id', $userFilter->room_attribute_ids);
                });
            }

            if (!empty($userFilter->event_property_ids)) {
                $query->whereHas('eventProperties', function ($q) use ($userFilter): void {
                    $q->whereIn('event_properties.id', $userFilter->event_property_ids);
                });
            }
        }

        return $query->get();
    }

    /**
     * Sortierung Tag → Raum-Position → Gewerk-Position → Beginn, wie zuvor per Collection::sortBy, aber mit
     * einmal je Schicht ermittelten Schlüsseln: sortBy las bei jedem Vergleich die gecasteten Attribute neu
     * (start_date erzeugt je Zugriff ein Carbon-Objekt) — bei einigen tausend Schichten über eine Sekunde.
     * Der Rohwert von start_date (DATE-Spalte, 'Y-m-d') sortiert wie die Carbon-Instanz; uasort ist stabil.
     *
     * @param \Illuminate\Support\Collection<int, Shift> $shifts
     * @return \Illuminate\Support\Collection<int, Shift>
     */
    private function sortForListView(\Illuminate\Support\Collection $shifts): \Illuminate\Support\Collection
    {
        $keys = [];
        foreach ($shifts as $index => $shift) {
            $keys[$index] = [
                $shift->getAttributes()['start_date'] ?? null,
                $shift->room->position ?? 0,
                $shift->craft->position ?? 0,
                $shift->start,
            ];
        }

        uasort($keys, static function (array $a, array $b): int {
            foreach ([0, 1, 2, 3] as $position) {
                $result = $a[$position] <=> $b[$position];
                if ($result !== 0) {
                    return $result;
                }
            }

            return 0;
        });

        $items = $shifts->all();
        $sorted = [];
        foreach (array_keys($keys) as $index) {
            $sorted[$index] = $items[$index];
        }

        return $shifts->make($sorted);
    }

    private function startDay(Shift $shift): string
    {
        $raw = $shift->getAttributes()['start_date'] ?? null;

        return is_string($raw) && preg_match('/^\d{4}-\d{2}-\d{2}(?:$|[ T])/', $raw) === 1
            ? substr($raw, 0, 10)
            : Carbon::parse($shift->start_date)->format('Y-m-d');
    }
}
