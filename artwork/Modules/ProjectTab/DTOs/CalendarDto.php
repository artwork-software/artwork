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

    public function setEventsAtAGlance(?SupportCollection $eventsAtAGlance): self
    {
        $this->eventsAtAGlance = $eventsAtAGlance;

        return $this;
    }

    public function setCalendar(?SupportCollection $calendar): self
    {
        $this->calendar = $calendar;

        return $this;
    }

    public function setDateValue(?array $dateValue): self
    {
        $this->dateValue = $dateValue;

        return $this;
    }

    public function setDays(?array $days): self
    {
        $this->days = $days;

        return $this;
    }

    public function setSelectedDate(?string $selectedDate): self
    {
        $this->selectedDate = $selectedDate;

        return $this;
    }

    public function setRooms(?Collection $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function setEvents(?CalendarEventCollectionResourceModel $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function setFilterOptions(?array $filterOptions): self
    {
        $this->filterOptions = $filterOptions;

        return $this;
    }

    public function setPersonalFilters(?SupportCollection $personalFilters): self
    {
        $this->personalFilters = $personalFilters;

        return $this;
    }

    public function setEventsWithoutRoom(?array $eventsWithoutRoom): self
    {
        $this->eventsWithoutRoom = $eventsWithoutRoom;

        return $this;
    }

    public function setUserFilters(?UserCalendarFilter $userFilters): self
    {
        $this->userFilters = $userFilters;

        return $this;
    }
}
