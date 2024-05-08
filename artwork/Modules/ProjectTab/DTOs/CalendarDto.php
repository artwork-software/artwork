<?php

namespace Artwork\Modules\ProjectTab\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
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

    public ?CalendarEventDto $events = null;

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

    public function setEvents(?CalendarEventDto $events): self
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

    public function getEventsAtAGlance(): ?SupportCollection
    {
        return $this->eventsAtAGlance;
    }

    public function getCalendar(): ?SupportCollection
    {
        return $this->calendar;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDateValue(): ?array
    {
        return $this->dateValue;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDays(): ?array
    {
        return $this->days;
    }

    public function getSelectedDate(): ?string
    {
        return $this->selectedDate;
    }

    public function getRooms(): ?Collection
    {
        return $this->rooms;
    }

    public function getEvents(): ?CalendarEventDto
    {
        return $this->events;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFilterOptions(): ?array
    {
        return $this->filterOptions;
    }

    public function getPersonalFilters(): ?SupportCollection
    {
        return $this->personalFilters;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventsWithoutRoom(): ?array
    {
        return $this->eventsWithoutRoom;
    }

    public function getUserFilters(): ?UserCalendarFilter
    {
        return $this->userFilters;
    }
}
