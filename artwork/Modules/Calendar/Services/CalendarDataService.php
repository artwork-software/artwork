<?php

namespace Artwork\Modules\Calendar\Services;

use App\Http\Controllers\FilterController;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Services\EventCollectorService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\FiltersRoomsBy;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalendarDataService
{
    use FiltersRoomsBy;

    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly FilterService $filterService,
        private readonly FilterController $filterController,
        private readonly UserService $userService,
        private readonly EventCollectorService $eventCollectorService,
    ) {
    }

    public function createCalendarData(
        Carbon $startDate,
        Carbon $endDate,
        ?Project $project,
        ?CalendarFilter $calendarFilter,
        ?Room $room = null,
        ?bool $desiresInventorySchedulingResource = false
    ): array {
        $periodArray = [];
        foreach (($calendarPeriod = CarbonPeriod::create($startDate, $endDate)) as $period) {
            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'day_string' => $period->shortDayName,
                'is_weekend' => $period->isWeekend(),
                'full_day' => $period->format('d.m.Y'),
                'short_day' => $period->format('d.m'),
                'week_number' => $period->weekOfYear,
                'is_monday' => $period->isMonday(),
                'month_number' => $period->month,
                'is_first_day_of_month' => $period->isSameDay($period->copy()->startOfMonth())
            ];
        }

        return [
            'days' => $periodArray,
            'dateValue' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
            // only used for dashboard -> default Dashboard should show Vuecal-Daily calendar with current day
            'calendarType' => $startDate->format('d.m.Y') === $endDate->format('d.m.Y') ?
                'daily' :
                'individual',
            // Selected Date is needed for change from individual Calendar to VueCal-Daily, so that vuecal knows which
            // date to load
            'selectedDate' => $startDate->format('Y-m-d') === $endDate->format('Y-m-d') ?
                $startDate->format('Y-m-d') :
                null,
            'roomsWithEvents' => empty($room) ?
                $this->eventCollectorService->collectEventsForRooms(
                    roomsWithEvents: $this->getFilteredRooms(
                        $startDate,
                        $endDate,
                        $calendarFilter
                    ),
                    calendarPeriod: $calendarPeriod,
                    calendarFilter: $calendarFilter,
                    project: $project,
                    desiresInventorySchedulingResource: $desiresInventorySchedulingResource
                ) :
                $this->eventCollectorService->collectEventsForRoom(
                    room: $room,
                    calendarPeriod: $calendarPeriod,
                    calendarFilter: $calendarFilter,
                    project: $project
                ),
            'eventsWithoutRoom' => empty($room) ?
                CalendarEventResource::collection(
                    $this->eventCollectorService->getEventsWithoutRoom(
                        $project,
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
                    )
                )->resolve() :
                [],
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
            'personalFilters' => $this->filterController->index(),
            'user_filters' => $this->userService->getAuthUser()->calendar_filter,
        ];
    }
}
