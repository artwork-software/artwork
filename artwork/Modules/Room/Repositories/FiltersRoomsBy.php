<?php

namespace Artwork\Modules\Room\Repositories;

use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\UserShiftCalendarFilter\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait FiltersRoomsBy
{
    public function getFilteredRooms(
        Carbon $startDate,
        Carbon $endDate,
        UserShiftCalendarFilter|UserCalendarFilter|null $calendarFilter
    ): EloquentCollection {
        return $this->roomRepository->getFilteredRoomsBy(
            $calendarFilter?->rooms,
            $calendarFilter?->room_attributes,
            $calendarFilter?->areas,
            $calendarFilter?->room_categories,
            $calendarFilter?->adjoining_not_loud,
            $calendarFilter?->adjoining_no_audience,
            $startDate,
            $endDate
        );
    }
}
