<?php

namespace Artwork\Modules\Event\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\UserShiftCalendarFilter\Models\UserShiftCalendarFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ShiftPlanDto extends BaseDto
{
    public ?Collection $events = null;

    public ?array $history = null;

    public ?Collection $crafts = null;

    public ?array $eventTypes = null;

    public ?Collection $projects = null;

    public ?SupportCollection $shiftPlan = null;

    public ?Collection $rooms = null;

    public ?array $days  = null;

    public ?array $filterOptions = null;

    public ?UserShiftCalendarFilter $userFilters = null;

    public ?array $dateValue = null;

    public ?SupportCollection $personalFilters = null;

    public ?string $selectedDate = null;

    public ?array $usersForShifts = null;

    public ?array $freelancersForShifts = null;

    public ?array $serviceProvidersForShifts = null;

    public ?Collection $shiftQualifications = null;

    public ?Collection $dayServices = null;


    public function setDayServices(?Collection $dayServices): self
    {
        $this->dayServices = $dayServices;

        return $this;
    }

    public function setEvents(?Collection $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function setHistory(?array $history): self
    {
        $this->history = $history;

        return $this;
    }

    public function setCrafts(?Collection $crafts): self
    {
        $this->crafts = $crafts;

        return $this;
    }

    public function setEventTypes(?array $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setProjects(?Collection $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function setShiftPlan(?SupportCollection $shiftPlan): self
    {
        $this->shiftPlan = $shiftPlan;

        return $this;
    }

    public function setRooms(?Collection $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function setDays(?array $days): self
    {
        $this->days = $days;

        return $this;
    }

    public function setFilterOptions(?array $filterOptions): self
    {
        $this->filterOptions = $filterOptions;

        return $this;
    }

    public function setUserFilters(?UserShiftCalendarFilter $userFilters): self
    {
        $this->userFilters = $userFilters;

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

    public function setSelectedDate(?string $selectedDate): self
    {
        $this->selectedDate = $selectedDate;

        return $this;
    }

    public function setUsersForShifts(?array $usersForShifts): self
    {
        $this->usersForShifts = $usersForShifts;

        return $this;
    }

    public function setFreelancersForShifts(?array $freelancersForShifts): self
    {
        $this->freelancersForShifts = $freelancersForShifts;

        return $this;
    }

    public function setServiceProvidersForShifts(?array $serviceProvidersForShifts): self
    {
        $this->serviceProvidersForShifts = $serviceProvidersForShifts;

        return $this;
    }

    public function setShiftQualifications(?Collection $shiftQualifications): self
    {
        $this->shiftQualifications = $shiftQualifications;

        return $this;
    }

    public function getEvents(): ?Collection
    {
        return $this->events;
    }

    /**
     * @return array<int, mixed>|null
     */
    public function getHistory(): ?array
    {
        return $this->history;
    }

    public function getCrafts(): ?Collection
    {
        return $this->crafts;
    }

    /**
     * @return array<int, EventTypeResource>|null
     */
    public function getEventTypes(): ?array
    {
        return $this->eventTypes;
    }

    public function getProjects(): ?Collection
    {
        return $this->projects;
    }

    public function getShiftPlan(): ?SupportCollection
    {
        return $this->shiftPlan;
    }

    public function getRooms(): ?Collection
    {
        return $this->rooms;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getDays(): ?array
    {
        return $this->days;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFilterOptions(): ?array
    {
        return $this->filterOptions;
    }

    public function getUserFilters(): ?UserShiftCalendarFilter
    {
        return $this->userFilters;
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

    public function getSelectedDate(): ?string
    {
        return $this->selectedDate;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUsersForShifts(): ?array
    {
        return $this->usersForShifts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFreelancersForShifts(): ?array
    {
        return $this->freelancersForShifts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getServiceProvidersForShifts(): ?array
    {
        return $this->serviceProvidersForShifts;
    }

    public function getShiftQualifications(): ?Collection
    {
        return $this->shiftQualifications;
    }

    public function getDayServices(): ?Collection
    {
        return $this->dayServices;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'events' => $this->getEvents(),
            'history' => $this->getHistory(),
            'crafts' => $this->getCrafts(),
            'eventTypes' => $this->getEventTypes(),
            'projects' => $this->getProjects(),
            'shiftPlan' => $this->getShiftPlan(),
            'rooms' => $this->getRooms(),
            'days' => $this->getDays(),
            'filterOptions' => $this->getFilterOptions(),
            'user_filters' => $this->getUserFilters(),
            'dateValue' => $this->getDateValue(),
            'personalFilters' => $this->getPersonalFilters(),
            'selectedDate' => $this->getSelectedDate(),
            'usersForShifts' => $this->getUsersForShifts(),
            'freelancersForShifts' => $this->getFreelancersForShifts(),
            'serviceProvidersForShifts' => $this->getServiceProvidersForShifts(),
            'shiftQualifications' => $this->getShiftQualifications(),
            'dayServices' => $this->getDayServices(),
        ];
    }
}
