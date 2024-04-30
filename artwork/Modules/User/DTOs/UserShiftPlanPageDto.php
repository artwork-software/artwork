<?php

namespace Artwork\Modules\User\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UserShiftPlanPageDto extends BaseDto
{
    public ?UserShowResource $userToEdit = null;

    public ?string $currentTab = null;

    public ?array $calendarData = null;

    public ?array $dateToShow = null;

    public ?Collection $vacationSelectCalendar = null;

    public ?array $createShowDate = null;

    public ?EloquentCollection $vacations = null;

    public ?EloquentCollection $availabilities = null;

    public ?string $showVacationsAndAvailabilitiesDate = null;

    public ?array $dateValue = null;

    public ?array $daysWithEvents = null;

    public ?float $totalPlannedWorkingHours = null;

    public ?EloquentCollection $rooms = null;

    public ?array $eventTypes = null;

    public ?EloquentCollection $projects = null;

    public ?EloquentCollection $shiftQualifications = null;

    public ?EloquentCollection $shifts = null;

    public function setUserToEdit(?UserShowResource $userToEdit): self
    {
        $this->userToEdit = $userToEdit;

        return $this;
    }

    public function setCurrentTab(?string $currentTab): self
    {
        $this->currentTab = $currentTab;

        return $this;
    }

    public function setCalendarData(?array $calendarData): self
    {
        $this->calendarData = $calendarData;

        return $this;
    }

    public function setDateToShow(?array $dateToShow): self
    {
        $this->dateToShow = $dateToShow;

        return $this;
    }

    public function setVacationSelectCalendar(?Collection $vacationSelectCalendar): self
    {
        $this->vacationSelectCalendar = $vacationSelectCalendar;

        return $this;
    }

    public function setCreateShowDate(?array $createShowDate): self
    {
        $this->createShowDate = $createShowDate;

        return $this;
    }

    public function setVacations(?EloquentCollection $vacations): self
    {
        $this->vacations = $vacations;

        return $this;
    }

    public function setAvailabilities(?EloquentCollection $availabilities): self
    {
        $this->availabilities = $availabilities;

        return $this;
    }

    public function setShowVacationsAndAvailabilitiesDate(?string $showVacationsAndAvailabilitiesDate): self
    {
        $this->showVacationsAndAvailabilitiesDate = $showVacationsAndAvailabilitiesDate;

        return $this;
    }

    public function setDateValue(?array $dateValue): self
    {
        $this->dateValue = $dateValue;

        return $this;
    }

    public function setDaysWithEvents(?array $daysWithEvents): self
    {
        $this->daysWithEvents = $daysWithEvents;

        return $this;
    }

    public function setTotalPlannedWorkingHours(?float $totalPlannedWorkingHours): self
    {
        $this->totalPlannedWorkingHours = $totalPlannedWorkingHours;

        return $this;
    }

    public function setRooms(?EloquentCollection $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function setEventTypes(?array $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setProjects(?EloquentCollection $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function setShiftQualifications(?EloquentCollection $shiftQualifications): self
    {
        $this->shiftQualifications = $shiftQualifications;

        return $this;
    }

    public function setShifts(?EloquentCollection $shifts): self
    {
        $this->shifts = $shifts;

        return $this;
    }

    public function getUserToEdit(): ?UserShowResource
    {
        return $this->userToEdit;
    }

    public function getCurrentTab(): ?string
    {
        return $this->currentTab;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getCalendarData(): ?array
    {
        return $this->calendarData;
    }

    /**
     * @return array<int, mixed>|null
     */
    public function getDateToShow(): ?array
    {
        return $this->dateToShow;
    }

    /**
     * @return Collection|null
     */
    public function getVacationSelectCalendar(): ?Collection
    {
        return $this->vacationSelectCalendar;
    }

    /**
     * @return array<int, string>|null
     */
    public function getCreateShowDate(): ?array
    {
        return $this->createShowDate;
    }

    public function getVacations(): ?EloquentCollection
    {
        return $this->vacations;
    }

    public function getAvailabilities(): ?EloquentCollection
    {
        return $this->availabilities;
    }

    public function getShowVacationsAndAvailabilitiesDate(): ?string
    {
        return $this->showVacationsAndAvailabilitiesDate;
    }

    /**
     * @return array<int, string>|null
     */
    public function getDateValue(): ?array
    {
        return $this->dateValue;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDaysWithEvents(): ?array
    {
        return $this->daysWithEvents;
    }

    public function getTotalPlannedWorkingHours(): ?float
    {
        return $this->totalPlannedWorkingHours;
    }

    public function getRooms(): ?EloquentCollection
    {
        return $this->rooms;
    }

    /**
     * @return array<int, EventTypeResource>|null
     */
    public function getEventTypes(): ?array
    {
        return $this->eventTypes;
    }

    public function getProjects(): ?EloquentCollection
    {
        return $this->projects;
    }

    public function getShiftQualifications(): ?EloquentCollection
    {
        return $this->shiftQualifications;
    }

    public function getShifts(): ?EloquentCollection
    {
        return $this->shifts;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_to_edit' => $this->getUserToEdit(),
            'currentTab' => $this->getCurrentTab(),
            'calendarData' => $this->getCalendarData(),
            'dateToShow' => $this->getDateToShow(),
            'vacationSelectCalendar' => $this->getVacationSelectCalendar(),
            'createShowDate' => $this->getCreateShowDate(),
            'vacations' => $this->getVacations(),
            'availabilities' => $this->getAvailabilities(),
            'showVacationsAndAvailabilitiesDate' => $this->getShowVacationsAndAvailabilitiesDate(),
            'dateValue' => $this->getDateValue(),
            'daysWithEvents' => $this->getDaysWithEvents(),
            'totalPlannedWorkingHours' => $this->getTotalPlannedWorkingHours(),
            'rooms' => $this->getRooms(),
            'eventTypes' => $this->getEventTypes(),
            'projects' => $this->getProjects(),
            'shiftQualifications' => $this->getShiftQualifications(),
            'shifts' => $this->getShifts()
        ];
    }
}
