<?php

namespace Artwork\Modules\Event\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class EventManagementDto extends BaseDto
{
    public ?array $eventTypes = null;

    public ?SupportCollection $calendar = null;

    public ?array $days = null;

    public ?array $dateValue = null;

    public ?string $calendarType = null;

    public ?string $selectedDate = null;

    public ?array $eventsWithoutRoom = null;

    public ?SupportCollection $eventsAtAGlance = null;

    public ?Collection $rooms = null;

    public ?CalendarEventDto $events = null;

    public ?array $filterOptions = null;

    public ?SupportCollection $personalFilters = null;

    public ?UserCalendarFilter $userFilters = null;

    public ?int $firstProjectTabId = null;

    public ?int $firstProjectCalendarTabId = null;

    public function setEventTypes(?array $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setCalendar(?SupportCollection $calendar): self
    {
        $this->calendar = $calendar;

        return $this;
    }

    public function setDays(?array $days): self
    {
        $this->days = $days;

        return $this;
    }

    public function setDateValue(?array $dateValue): self
    {
        $this->dateValue = $dateValue;

        return $this;
    }

    public function setCalendarType(?string $calendarType): self
    {
        $this->calendarType = $calendarType;

        return $this;
    }

    public function setSelectedDate(?string $selectedDate): self
    {
        $this->selectedDate = $selectedDate;

        return $this;
    }

    public function setEventsWithoutRoom(?array $eventsWithoutRoom): self
    {
        $this->eventsWithoutRoom = $eventsWithoutRoom;

        return $this;
    }

    public function setEventsAtAGlance(?SupportCollection $eventsAtAGlance): self
    {
        $this->eventsAtAGlance = $eventsAtAGlance;

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

    public function setUserFilters(?UserCalendarFilter $userFilters): self
    {
        $this->userFilters = $userFilters;

        return $this;
    }

    public function setFirstProjectTabId(?int $firstProjectTabId): self
    {
        $this->firstProjectTabId = $firstProjectTabId;

        return $this;
    }

    public function setFirstProjectCalendarTabId(?int $firstProjectCalendarTabId): self
    {
        $this->firstProjectCalendarTabId = $firstProjectCalendarTabId;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventTypes(): ?array
    {
        return $this->eventTypes;
    }

    public function getCalendar(): ?SupportCollection
    {
        return $this->calendar;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDays(): ?array
    {
        return $this->days;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDateValue(): ?array
    {
        return $this->dateValue;
    }

    public function getCalendarType(): ?string
    {
        return $this->calendarType;
    }

    public function getSelectedDate(): ?string
    {
        return $this->selectedDate;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventsWithoutRoom(): ?array
    {
        return $this->eventsWithoutRoom;
    }

    public function getEventsAtAGlance(): ?SupportCollection
    {
        return $this->eventsAtAGlance;
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

    public function getUserFilters(): ?UserCalendarFilter
    {
        return $this->userFilters;
    }

    public function getFirstProjectTabId(): ?int
    {
        return $this->firstProjectTabId;
    }

    public function getFirstProjectCalendarTabId(): ?int
    {
        return $this->firstProjectCalendarTabId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eventTypes' => $this->getEventTypes(),
            'calendar' => $this->getCalendar(),
            'days' => $this->getDays(),
            'dateValue' => $this->getDateValue(),
            'calendarType' => $this->getCalendarType(),
            'selectedDate' => $this->getSelectedDate(),
            'eventsWithoutRoom' => $this->getEventsWithoutRoom(),
            'eventsAtAGlance' => $this->getEventsAtAGlance(),
            'rooms' => $this->getRooms(),
            'events' => $this->getEvents(),
            'filterOptions' => $this->getFilterOptions(),
            'personalFilters' => $this->getPersonalFilters(),
            'user_filters' => $this->getUserFilters(),
            'first_project_tab_id' => $this->getFirstProjectTabId(),
            'first_project_calendar_tab_id' => $this->getFirstProjectCalendarTabId()
        ];
    }
}
