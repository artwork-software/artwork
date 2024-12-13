<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CalendarService
{
    public function createVacationAndAvailabilityPeriodCalendar($month = null): Collection
    {
        $date = Carbon::today();
        $daysInMonth = $date->daysInMonth;
        $firstDayOfMonth = Carbon::now()->startOfMonth();
        $lastDayOfMonth = Carbon::now()->endOfMonth();
        if ($month) {
            $date = Carbon::parse($month);
            $daysInMonth = $date->daysInMonth;
            $firstDayOfMonth = Carbon::parse($month)->startOfMonth();
            $lastDayOfMonth = Carbon::parse($month)->endOfMonth();
        }

        // Assuming you start the week on Monday
        $paddingStart = $firstDayOfMonth->dayOfWeekIso - 1;
        $paddingEnd = 7 - $lastDayOfMonth->dayOfWeekIso;

        $days = collect()->range(1 - $paddingStart, $daysInMonth + $paddingEnd)
            ->map(function ($day) use ($date) {
                $currentDay = $date->copy()->startOfMonth()->addDays($day - 1);
                return [
                    'date' => $currentDay->format('Y-m-d'),
                    'day' => $currentDay->day,
                    'inMonth' => $currentDay->month === $date->month,
                    'isToday' => $currentDay->isToday(),
                ];
            });

        return $days->chunk(7);
    }

    /**
     * @return array<string, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    //@todo: fix phpcs error - fix complexity
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getAvailabilityData(Available $available, $month = null): array
    {
        $vacationDays = $available->vacations()->orderBy('date', 'ASC')->get();
        $availabilityDays = $available->availabilities()->orderBy('date', 'ASC')->get();

        $currentMonth = Carbon::now()->startOfMonth();

        if ($month) {
            $currentMonth = Carbon::parse($month)->startOfMonth();
        }

        $startDate = $currentMonth->copy()->startOfWeek();
        $endDate = $currentMonth->copy()->endOfMonth()->endOfWeek();

        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $onVacation = false;
            $hasAvailability = false;
            $hasConflict = false;
            $weekNumber = $currentDate->weekOfYear;
            $day = $currentDate->day;
            foreach ($vacationDays as $vacationDay) {
                if ($currentDate->isSameDay($vacationDay->date)) {
                    $onVacation = true;
                }
            }

            foreach ($availabilityDays as $availabilityDay) {
                if ($currentDate->isSameDay($availabilityDay->date)) {
                    $hasAvailability = true;
                }
            }

            // check if vacation and availability conflicts
            foreach ($vacationDays as $vacationDay) {
                if ($vacationDay->conflicts->count() > 0 && $currentDate->isSameDay($vacationDay->date)) {
                    $hasConflict = true;
                }
            }

            foreach ($availabilityDays as $availabilityDay) {
                if ($availabilityDay->conflicts->count() > 0 && $currentDate->isSameDay($availabilityDay->date)) {
                    $hasConflict = true;
                }
            }


            if (!isset($calendarData[$weekNumber])) {
                $calendarData[$weekNumber] = ['weekNumber' => $weekNumber, 'days' => []];
            }

            $notInMonth = !$currentDate->isSameMonth($currentMonth);

            $calendarData[$weekNumber]['days'][] = [
                'day' => $day,
                'notInMonth' => $notInMonth,
                'onVacation' => $onVacation,
                'hasAvailability' => $hasAvailability,
                'hasConflict' => $hasConflict,
                'day_formatted' => $currentDate->format('Y-m-d'),
                'isToday' => $currentDate->isToday(),
            ];

            $currentDate->addDay();
        }

        $dateToShow = [
            $currentMonth->locale(\session()->get('locale') ?? config('app.fallback_locale'))->isoFormat('MMMM YYYY'),
            $currentMonth->copy()->startOfMonth()->toDate()
        ];

        return [
            array_values($calendarData),
            $dateToShow
        ];
    }

    /**
     * @return array<int, MinimalCalendarEventResource>
     */
    public function getEventsAtAGlance(
        $startDate,
        $endDate,
        UserCalendarFilter $userCalendarFilter,
        ?Project $project = null,
    ): array {
        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        $actualEvents = $eventsForRoom = [];

        $eventsQuery = $this->filterEvents(
            $startDate,
            $endDate,
            $userCalendarFilter,
            null,
            $project
        )->with(
            [
                'room',
                'creator',
                'project',
                'project.managerUsers',
                'project.state',
                'shifts',
                'shifts.craft',
                'shifts.users',
                'shifts.freelancer',
                'shifts.serviceProvider',
                'shifts.shiftsQualifications',
                'subEvents.event',
                'subEvents.event.room'
            ]
        )->orderBy('start_time');

        foreach ($eventsQuery->get()->all() as $event) {
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $event->start_time;
            $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ? $calendarPeriod->end : $event->end_time;
            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                $actualEvents[$dateKey][] = $event;
            }
        }

        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                //immediately resolve resource to free used memory
                'events' => MinimalCalendarEventResource::collection($value)->resolve()
            ];
        }

        return $eventsForRoom;
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function filterEvents(
        Carbon $startDate,
        Carbon $endDate,
        UserCalendarFilter $userCalendarFilter,
        ?Room $room,
        ?Project $project
    ): Builder|HasMany {
        $isLoud = $userCalendarFilter->is_loud ?? false;
        $isNotLoud = $userCalendarFilter->is_not_loud ?? false;
        $hasAudience = $userCalendarFilter->has_audience ?? false;
        $hasNoAudience = $userCalendarFilter->has_no_audience ?? false;
        $showAdjoiningRooms = $userCalendarFilter->show_adjoining_rooms ?? false;
        $eventTypeIds = $userCalendarFilter->event_types ?? null;
        $roomIds = $userCalendarFilter->rooms ?? null;
        $areaIds = $userCalendarFilter->areas ?? null;
        $roomAttributeIds = $userCalendarFilter->room_attributes ?? null;
        $roomCategoryIds = $userCalendarFilter->room_categories ?? null;
        //@todo: clarify how to build query better (maybe start with rooms)
        //$adjoiningNotLoud = $userCalendarFilter->adjoining_not_loud ?? null;
        //$adjoiningWithoutAudience = $userCalendarFilter->adjoining_no_audience ?? null;

        return Event::query()
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id ?? null))
            ->when($room, fn(Builder $builder) => $builder->where('room_id', $room->id ?? null))
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn(Builder $builder) => $builder->whereHas(
                    'room',
                    fn(Builder $roomBuilder) => $roomBuilder
                        ->when($roomIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                        ->when($areaIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                        ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                        ->when(
                            $roomAttributeIds,
                            fn(Builder $roomBuilder) => $roomBuilder
                                ->whereHas(
                                    'attributes',
                                    fn(Builder $roomAttributeBuilder) => $roomAttributeBuilder
                                        ->whereIn('room_attributes.id', $roomAttributeIds)
                                )
                        )
                        ->when(
                            $roomCategoryIds,
                            fn(Builder $roomBuilder) => $roomBuilder
                                ->whereHas(
                                    'categories',
                                    fn(Builder $roomCategoryBuilder) => $roomCategoryBuilder
                                        ->whereIn('room_categories.id', $roomCategoryIds)
                                )
                        )
                        ->without(['admins'])
                )
            )
            ->unless(
                empty($eventTypeIds),
                function ($query) use ($eventTypeIds) {
                    return $query->where(
                        function ($query) use ($eventTypeIds): void {
                            $query->whereIn('event_type_id', $eventTypeIds)
                                ->orWhereHas(
                                    'subEvents',
                                    function ($query) use ($eventTypeIds): void {
                                        $query->whereIn('event_type_id', $eventTypeIds);
                                    }
                                );
                        }
                    );
                }
            )
            ->unless(!$hasAudience, fn(Builder $builder) => $builder->where('audience', true))
            ->unless(!$hasNoAudience, fn(Builder $builder) => $builder->where('audience', false))
            ->unless(!$isLoud, fn(Builder $builder) => $builder->where('is_loud', true))
            ->unless(!$isNotLoud, fn(Builder $builder) => $builder->where('is_loud', false))
            ->when($startDate, fn(Builder $builder) => $builder->startAndEndTimeOverlap($startDate, $endDate));
    }
}
