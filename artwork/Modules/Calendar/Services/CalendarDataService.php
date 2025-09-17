<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use Artwork\Modules\Calendar\DTO\CalendarPeriodDTO;
use Artwork\Modules\Calendar\DTO\RoomDTO;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

readonly class CalendarDataService
{
    public function __construct(
        private UserService $userService,
        private ProjectService $projectService,
    ) {}

    public function createCalendarPeriodDto($startDate, $endDate, User $user, bool $extraRow = true): array
    {
        if (!$startDate || !$endDate) {
            return [];
        }

        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);

        // Einmal alle Feiertage holen (per-day Filter lokal)
        $holidaysByDate = $this->getHolidaysForRange($startDate, $endDate)
            ->groupBy(fn(CalendarHolidayDTO $h) => $h->date);

        $hoursOfDay = $user->getAttribute('daily_view')
            ? array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23))
            : [];

        $periodArray = [];
        foreach ($calendarPeriod as $period) {
            if ($extraRow && $period->isMonday()) {
                $periodArray[] = [
                    'isExtraRow'  => true,
                    'weekNumber'  => $period->weekOfYear,
                ];
            }

            $periodArray[] = new CalendarPeriodDTO(
                day: $period->format('d.m.'),
                dayString: $period->shortDayName,
                isWeekend: $period->isWeekend(),
                fullDay: $period->format('d.m.Y'),
                shortDay: $period->format('d.m'),
                withoutFormat: $period->toDateString(),
                fullDayDisplay: $period->format('d.m.y'),
                weekNumber: $period->weekOfYear,
                isMonday: $period->isMonday(),
                monthNumber: $period->month,
                isSunday: $period->isSunday(),
                isFirstDayOfMonth: $period->isSameDay($period->copy()->firstOfMonth()),
                addWeekSeparator: $period->isSunday(),
                holidays: $holidaysByDate->get($period->toDateString(), collect())->values(),
                hoursOfDay: $hoursOfDay,
                isExtraRow: false,
            );
        }

        return $periodArray;
    }

    /**
     * Optimierte Room-Liste:
     * - nur benötigte Spalten
     * - `withExists` liefert `has_events` ohne N+1
     * - `with('admins')` um N+1 für Admins zu vermeiden
     */
    public function getFilteredRooms(UserFilter $filter, $userCalendarSettings, $startDate, $endDate)
    {
        $rooms = Room::query()
            ->select(['id','name','temporary','start_date','end_date','position'])
            ->where('relevant_for_disposition', true)
            ->unlessRoomIds($filter?->room_ids)
            ->unlessRoomAttributeIds($filter?->room_attribute_ids)
            ->unlessAreaIds($filter?->area_ids)
            ->unlessRoomCategoryIds($filter?->room_category_ids)
            ->with([
                'admins:id', // wir brauchen nur die IDs
            ])
            // has_events als EXISTS-Subquery (überlappend im Zeitraum)
            /*->withExists(['events as has_events' => function (Builder $q) use ($filter, $startDate, $endDate) {
                $q->when(!empty($filter->event_type_ids), fn($qq) => $qq->whereIn('events.event_type_id', $filter->event_type_ids))
                    ->where(function ($qq) use ($startDate, $endDate) {
                        $qq->whereBetween('start_time', [$startDate, $endDate])
                            ->orWhereBetween('end_time',   [$startDate, $endDate])
                            ->orWhere(function ($nested) use ($startDate, $endDate) {
                                $nested->where('start_time', '<=', $startDate)
                                    ->where('end_time',   '>=', $endDate);
                            });
                    });
            }])
            // Optional: nur Räume mit Events in Zeitraum
            ->when(
                $userCalendarSettings?->hide_unoccupied_rooms,
                fn($q) => $q->where('has_events', true)
            )*/
            ->orderBy('position')
            ->get()
            // temporäre Räume auf Zeitraum prüfen
            ->filter(fn($room) => !$room->temporary || $this->datesOverlap($room->start_date, $room->end_date, $startDate, $endDate))
            ->values();

        return $rooms->map(fn($room) => new RoomDTO(
            id: $room->id,
            name: $room->name,
            has_events: (bool)$room->has_events,
            admins: $room->admins->pluck('id')->toArray()
        ));
    }

    public function getCalendarDateRange(
        UserCalendarSettings $userCalendarSettings,
        UserFilter $userCalendarFilter,
        ?Project $project = null
    ): array {
        $today = Carbon::now();

        if (!$userCalendarSettings->getAttribute('use_project_time_period')) {
            return $this->userService->getUserCalendarFilterDatesOrDefault($userCalendarFilter);
        }

        if (!$project) {
            $project = $this->projectService->findById($userCalendarSettings->getAttribute('time_period_project_id'));
        }

        return $this->getProjectDateRange($project, $today);
    }

    protected function getProjectDateRange($project, Carbon $today): array
    {
        if (!$project) {
            return [$today->startOfDay(), $today->endOfDay()];
        }

        $firstEvent  = $this->projectService->getFirstEventInProject($project);
        $latestEvent = $this->projectService->getLatestEndingEventInProject($project);

        $endDate = $latestEvent ? $latestEvent->getAttribute('end_time')->copy()->endOfDay() : $today->endOfDay();

        return [
            $firstEvent ? $firstEvent->getAttribute('start_time')->startOfDay() : $today->startOfDay(),
            $endDate,
        ];
    }

    private function datesOverlap(?Carbon $start1, ?Carbon $end1, ?Carbon $start2, ?Carbon $end2): bool
    {
        if ($start1 === null || $end1 === null || $start2 === null || $end2 === null) {
            return true;
        }
        return $start1 <= $end2 && $start2 <= $end1;
    }

    /**
     * Feiertage einmal für Range
     * @return SupportCollection<CalendarHolidayDTO>
     */
    private function getHolidaysForRange(Carbon $start, Carbon $end): SupportCollection
    {
        return Holiday::select(['id','name','date','end_date','color','yearly'])
            ->where(function (Builder $q) use ($start, $end) {
                $q->whereBetween('date',     [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date',[$start->toDateString(), $end->toDateString()])
                    ->orWhere(function (Builder $nested) use ($start, $end) {
                        $nested->where('date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    })
                    ->orWhere(function (Builder $nested) use ($start, $end) {
                        // jährliche Gedenktage
                        $nested->where('yearly', true)
                            ->whereBetween(\DB::raw('DATE_FORMAT(date, "%m-%d")'), [$start->format('m-d'), $end->format('m-d')]);
                    });
            })
            ->with(['subdivisions' => fn($q) => $q->select('name')])
            ->get()
            ->map(fn($holiday) => new CalendarHolidayDTO(
                name: $holiday->name,
                date: $holiday->date->toDateString(),
                end_date: $holiday->end_date->toDateString(),
                color: $holiday->color,
                subdivisions: $holiday->subdivisions->pluck('name')->toArray(),
            ));
    }
}
