<?php

namespace Artwork\Modules\ProjectTab\DTOs;

use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Models\UserCalendarFilter;
use Artwork\Core\Abstracts\BaseDto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CalendarDto extends BaseDto
{
    public ?SupportCollection $eventsAtAGlance = null;

    public ?SupportCollection $calendar = null;

    public ?array $dateValue = null;

    public ?array $days = null;

    public ?string $selectedDate = null;

    public ?Collection $rooms = null;

    public ?CalendarEventCollectionResourceModel $events = null;

    public ?array $filterOptions = null;

    public ?SupportCollection $personalFilters = null;

    public ?array $eventsWithoutRoom = null;

    public ?UserCalendarFilter $userFilters = null;

    public function setEventsAtAGlance(?SupportCollection $eventsAtAGlance): void
    {
        $this->eventsAtAGlance = $eventsAtAGlance;
    }

    public function setCalendar(?SupportCollection $calendar): void
    {
        $this->calendar = $calendar;
    }

    public function setDateValue(?array $dateValue): void
    {
        $this->dateValue = $dateValue;
    }

    public function setDays(?array $days): void
    {
        $this->days = $days;
    }

    public function setSelectedDate(?string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function setRooms(?Collection $rooms): void
    {
        $this->rooms = $rooms;
    }

    public function setEvents(?CalendarEventCollectionResourceModel $events): void
    {
        $this->events = $events;
    }

    public function setFilterOptions(?array $filterOptions): void
    {
        $this->filterOptions = $filterOptions;
    }

    public function setPersonalFilters(?SupportCollection $personalFilters): void
    {
        $this->personalFilters = $personalFilters;
    }

    public function setEventsWithoutRoom(?array $eventsWithoutRoom): void
    {
        $this->eventsWithoutRoom = $eventsWithoutRoom;
    }

    public function setUserFilters(?UserCalendarFilter $userFilters): void
    {
        $this->userFilters = $userFilters;
    }
}
