<?php

namespace Artwork\Modules\Room\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\Room\Http\Resources\RoomCalendarResource;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ShowDto extends BaseDto
{
    public ?RoomCalendarResource $room = null;

    public ?array $rooms = null;

    public ?bool $isRoomAdmin = null;

    public ?array $eventTypes = null;

    public ?array $projects = null;

    public ?Collection $availableCategories = null;

    public ?SupportCollection $roomCategoryIds = null;

    public ?array $roomCategories = null;

    public ?Collection $availableAttributes = null;

    public ?SupportCollection $roomAttributeIds = null;

    public ?array $roomAttributes = null;

    public ?Collection $availableRooms = null;

    public ?SupportCollection $adjoiningRoomIds = null;

    public ?array $adjoiningRooms = null;

    public ?SupportCollection $calendar = null;

    public ?array $days = null;

    public ?array $eventsWithoutRoom = null;

    public ?array $filterOptions = null;

    public ?array $dateValue = null;

    public ?SupportCollection $personalFilters = null;

    public ?string $calendarType = null;

    public ?string $selectedDate = null;

    public ?UserCalendarFilter $userFilters = null;

    public ?int $firstProjectTabId = null;

    public ?int $firstProjectCalendarTabId = null;

    public function setRoom(?RoomCalendarResource $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function setRooms(?array $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function setIsRoomAdmin(?bool $isRoomAdmin): self
    {
        $this->isRoomAdmin = $isRoomAdmin;

        return $this;
    }

    public function setEventTypes(?array $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setProjects(?array $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function setAvailableCategories(?Collection $availableCategories): self
    {
        $this->availableCategories = $availableCategories;

        return $this;
    }

    public function setRoomCategoryIds(?SupportCollection $roomCategoryIds): self
    {
        $this->roomCategoryIds = $roomCategoryIds;

        return $this;
    }

    public function setRoomCategories(?array $roomCategories): self
    {
        $this->roomCategories = $roomCategories;

        return $this;
    }

    public function setAvailableAttributes(?Collection $availableAttributes): self
    {
        $this->availableAttributes = $availableAttributes;

        return $this;
    }

    public function setRoomAttributeIds(?SupportCollection $roomAttributeIds): self
    {
        $this->roomAttributeIds = $roomAttributeIds;

        return $this;
    }

    public function setRoomAttributes(?array $roomAttributes): self
    {
        $this->roomAttributes = $roomAttributes;

        return $this;
    }

    public function setAvailableRooms(?Collection $availableRooms): self
    {
        $this->availableRooms = $availableRooms;

        return $this;
    }

    public function setAdjoiningRoomIds(?SupportCollection $adjoiningRoomIds): self
    {
        $this->adjoiningRoomIds = $adjoiningRoomIds;

        return $this;
    }

    public function setAdjoiningRooms(?array $adjoiningRooms): self
    {
        $this->adjoiningRooms = $adjoiningRooms;

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

    public function setEventsWithoutRoom(?array $eventsWithoutRoom): self
    {
        $this->eventsWithoutRoom = $eventsWithoutRoom;

        return $this;
    }

    public function setFilterOptions(?array $filterOptions): self
    {
        $this->filterOptions = $filterOptions;

        return $this;
    }

    public function setDateValue(?array $dateValue): self
    {
        $this->dateValue = $dateValue;

        return $this;
    }

    public function setPersonalFilters(?SupportCollection $personalFilters): self
    {
        $this->personalFilters = $personalFilters;

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

    public function getRoom(): ?RoomCalendarResource
    {
        return $this->room;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getRooms(): ?array
    {
        return $this->rooms;
    }

    public function getIsRoomAdmin(): ?bool
    {
        return $this->isRoomAdmin;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getEventTypes(): ?array
    {
        return $this->eventTypes;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getProjects(): ?array
    {
        return $this->projects;
    }

    public function getAvailableCategories(): ?Collection
    {
        return $this->availableCategories;
    }

    public function getRoomCategoryIds(): ?SupportCollection
    {
        return $this->roomCategoryIds;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getRoomCategories(): ?array
    {
        return $this->roomCategories;
    }

    public function getAvailableAttributes(): ?Collection
    {
        return $this->availableAttributes;
    }

    public function getRoomAttributeIds(): ?SupportCollection
    {
        return $this->roomAttributeIds;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getRoomAttributes(): ?array
    {
        return $this->roomAttributes;
    }

    public function getAvailableRooms(): ?Collection
    {
        return $this->availableRooms;
    }

    public function getAdjoiningRoomIds(): ?SupportCollection
    {
        return $this->adjoiningRoomIds;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getAdjoiningRooms(): ?array
    {
        return $this->adjoiningRooms;
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
     * @return array<int, array<string, mixed>>|null
     */
    public function getEventsWithoutRoom(): ?array
    {
        return $this->eventsWithoutRoom;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFilterOptions(): ?array
    {
        return $this->filterOptions;
    }

    /**
     * @return array<int, string>|null
     */
    public function getDateValue(): ?array
    {
        return $this->dateValue;
    }

    public function getPersonalFilters(): ?SupportCollection
    {
        return $this->personalFilters;
    }

    public function getCalendarType(): ?string
    {
        return $this->calendarType;
    }

    public function getSelectedDate(): ?string
    {
        return $this->selectedDate;
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
            'room' => $this->getRoom(),
            'rooms' => $this->getRooms(),
            'is_room_admin' => $this->getIsRoomAdmin(),
            'event_types' => $this->getEventTypes(),
            'projects' => $this->getProjects(),
            'available_categories' => $this->getAvailableCategories(),
            'roomCategoryIds' => $this->getRoomCategoryIds(),
            'roomCategories' => $this->getRoomCategories(),
            'available_attributes' => $this->getAvailableAttributes(),
            'roomAttributeIds' => $this->getRoomAttributeIds(),
            'roomAttributes' => $this->getRoomAttributes(),
            'available_rooms' => $this->getAvailableRooms(),
            'adjoiningRoomIds' => $this->getAdjoiningRoomIds(),
            'adjoiningRooms' => $this->getAdjoiningRooms(),
            'calendar' => $this->getCalendar(),
            'days' => $this->getDays(),
            'eventsWithoutRoom' => $this->getEventsWithoutRoom(),
            'filterOptions' => $this->getFilterOptions(),
            'dateValue' => $this->getDateValue(),
            'personalFilters' => $this->getPersonalFilters(),
            'calendarType' => $this->getCalendarType(),
            'selectedDate' => $this->getSelectedDate(),
            'user_filters' => $this->getUserFilters(),
            'first_project_tab_id' => $this->getFirstProjectTabId(),
            'first_project_calendar_tab_id' => $this->getFirstProjectCalendarTabId(),
        ];
    }
}
