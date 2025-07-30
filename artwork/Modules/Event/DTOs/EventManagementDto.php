<?php

namespace Artwork\Modules\Event\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Inertia\Inertia;

class EventManagementDto extends BaseDto
{
    public ?array $eventTypes = null;

    public ?array $calendar = null;

    public ?array $days = null;

    public ?array $months = null;

    public ?array $dateValue = null;

    public ?string $calendarType = null;

    public ?string $selectedDate = null;

    public ?array $eventsWithoutRoom = null;

    public ?array $eventsAtAGlance = null;

    public ?Collection $rooms = null;

    public ?Collection $eventStatuses = null;

    public ?CalendarEventDto $events = null;

    public ?array $filterOptions = null;

    public ?SupportCollection $personalFilters = null;

    public ?UserCalendarFilter $userFilters = null;

    public ?int $firstProjectTabId = null;

    public ?int $firstProjectCalendarTabId = null;

    public ?int $firstProjectShiftTabId = null;

    public Collection $areas;

    public ?bool $show_artists;

    public ?string $projectNameUsedForProjectTimePeriod = null;

    public ?SupportCollection $eventProperties = null;

    public function getEventStatuses(): ?Collection
    {
        return $this->eventStatuses;
    }

    public function setEventStatuses(?Collection $eventStatuses): self
    {
        $this->eventStatuses = $eventStatuses;

        return $this;
    }

    public function getAreas(): Collection
    {
        return $this->areas;
    }

    public function setAreas(Collection $areas): self
    {
        $this->areas = $areas;

        return $this;
    }

    public function setEventTypes(?array $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setCalendar(?array $calendar): self
    {
        $this->calendar = $calendar;

        return $this;
    }

    public function setDays(?array $days): self
    {
        $this->days = $days;

        return $this;
    }

    public function setMonths(?array $months): self
    {
        $this->months = $months;

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

    public function setEventsAtAGlance(?array $eventsAtAGlance): self
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

    public function setUserFilters(?UserFilter $userFilters): self
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

    public function setFirstProjectShiftTabId(?int $firstProjectShiftTabId): self
    {
        $this->firstProjectShiftTabId = $firstProjectShiftTabId;

        return $this;
    }

    public function setProjectNameUsedForProjectTimePeriod(?string $projectNameUsedForProjectTimePeriod): self
    {
        $this->projectNameUsedForProjectTimePeriod = $projectNameUsedForProjectTimePeriod;

        return $this;
    }

    public function setShowArtists(?bool $showArtists): self
    {
        $this->show_artists = $showArtists;

        return $this;
    }

    public function setEventProperties(?SupportCollection $eventProperties): self
    {
        $this->eventProperties = $eventProperties;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventTypes(): ?array
    {
        return $this->eventTypes;
    }

    public function getCalendar(): ?array
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
    public function getMonths(): ?array
    {
        return $this->months;
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

    /**
     * @return array<int, MinimalCalendarEventResource>|null
     */
    public function getEventsAtAGlance(): ?array
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

    public function getUserFilters(): ?UserFilter
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

    public function getFirstProjectShiftTabId(): ?int
    {
        return $this->firstProjectShiftTabId;
    }

    public function getProjectNameUsedForProjectTimePeriod(): ?string
    {
        return $this->projectNameUsedForProjectTimePeriod;
    }

    public function getShowArtists(): ?bool
    {
        return $this->show_artists;
    }

    public function getEventProperties(): ?SupportCollection
    {
        return $this->eventProperties;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eventTypes' => Inertia::lazy(static fn () => EventType::all()),
            'eventStatuses' => Inertia::lazy(static fn () => EventStatus::orderBy('order')->get()),
            'calendar' => $this->getCalendar(),
            'days' => $this->getDays(),
            'months' => $this->getMonths(),
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
            'first_project_calendar_tab_id' => $this->getFirstProjectCalendarTabId(),
            'first_project_shift_tab_id' => $this->getFirstProjectShiftTabId(),
            'areas' => $this->getAreas(),
            'projectNameUsedForProjectTimePeriod' => $this->getProjectNameUsedForProjectTimePeriod(),
            //'eventStatuses' => $this->getEventStatuses(),
            'show_artists' => $this->getShowArtists(),
            'event_properties' => $this->getEventProperties()
        ];
    }
}
