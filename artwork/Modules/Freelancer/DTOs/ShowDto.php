<?php

namespace Artwork\Modules\Freelancer\DTOs;

use Closure;
use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ShowDto extends BaseDto
{
    public ?array $freelancer = null;

    public ?Collection $freelancerToEditWholeWeekDatePeriodVacations = null;

    public ?array $calendarData = null;

    public ?array $dateToShow = null;

    public ?Collection $vacations = null;

    public SupportCollection|Closure|null $vacationSelectCalendar = null;

    public ?array $createShowDate = null;

    public ?string $showVacationsAndAvailabilitiesDate = null;

    public ?array $dateValue = null;

    public ?array $wholeWeekDatePeriod = null;

    public ?array $eventsWithTotalPlannedWorkingHours = null;

    public ?float $totalPlannedWorkingHours = null;

    public Collection|Closure|null $rooms = null;

    public array|Closure|null $eventTypes = null;

    public Collection|Closure|null $projects = null;

    public Collection|Closure|null $shifts = null;

    public ?Collection $availabilities = null;

    public Collection|Closure|null $shiftQualifications = null;

    public int|Closure|null $firstProjectShiftTabId = null;

    public array|Closure|null $daysWithData = null;

    public Collection|Closure|null $crafts = null;

    public function setFreelancer(?array $freelancer): self
    {
        $this->freelancer = $freelancer;

        return $this;
    }

    public function setFreelancerToEditWholeWeekDatePeriodVacations(?Collection $vacations): self
    {
        $this->freelancerToEditWholeWeekDatePeriodVacations = $vacations;

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

    public function setVacations(?Collection $vacations): self
    {
        $this->vacations = $vacations;

        return $this;
    }

    public function setVacationSelectCalendar(SupportCollection|Closure|null $vacationSelectCalendar): self
    {
        $this->vacationSelectCalendar = $vacationSelectCalendar;

        return $this;
    }

    public function setCreateShowDate(?array $createShowDate): self
    {
        $this->createShowDate = $createShowDate;

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

    /**
     * @param array|null $wholeWeekDatePeriod
     */
    public function setWholeWeekDatePeriod(?array $wholeWeekDatePeriod): self
    {
        $this->wholeWeekDatePeriod = $wholeWeekDatePeriod;

        return $this;
    }

    public function setEventsWithTotalPlannedWorkingHours(?array $eventsWithTotalPlannedWorkingHours): self
    {
        $this->eventsWithTotalPlannedWorkingHours = $eventsWithTotalPlannedWorkingHours;

        return $this;
    }

    public function setTotalPlannedWorkingHours(?float $totalPlannedWorkingHours): self
    {
        $this->totalPlannedWorkingHours = $totalPlannedWorkingHours;

        return $this;
    }

    public function setRooms(Collection|Closure|null $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function setEventTypes(array|Closure|null $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setProjects(Collection|Closure|null $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function setShifts(Collection|Closure|null $shifts): self
    {
        $this->shifts = $shifts;

        return $this;
    }

    public function setAvailabilities(?Collection $availabilities): self
    {
        $this->availabilities = $availabilities;

        return $this;
    }

    public function setShiftQualifications(Collection|Closure|null $shiftQualifications): self
    {
        $this->shiftQualifications = $shiftQualifications;

        return $this;
    }

    public function setFirstProjectShiftTabId(int|Closure|null $firstProjectShiftTabId): self
    {
        $this->firstProjectShiftTabId = $firstProjectShiftTabId;

        return $this;
    }

    public function setDaysWithData(array|Closure|null $daysWithData): self
    {
        $this->daysWithData = $daysWithData;

        return $this;
    }

    public function setCrafts(Collection|Closure|null $crafts): self
    {
        $this->crafts = $crafts;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFreelancer(): ?array
    {
        return $this->freelancer;
    }

    public function getFreelancerToEditWholeWeekDatePeriodVacations(): ?Collection
    {
        return $this->freelancerToEditWholeWeekDatePeriodVacations;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCalendarData(): ?array
    {
        return $this->calendarData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDateToShow(): ?array
    {
        return $this->dateToShow;
    }

    public function getVacations(): ?Collection
    {
        return $this->vacations;
    }

    public function getVacationSelectCalendar(): SupportCollection|Closure|null
    {
        return $this->vacationSelectCalendar;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCreateShowDate(): ?array
    {
        return $this->createShowDate;
    }

    public function getShowVacationsAndAvailabilitiesDate(): ?string
    {
        return $this->showVacationsAndAvailabilitiesDate;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDateValue(): ?array
    {
        return $this->dateValue;
    }

    /**
     * @return array<int, string>|null
     */
    public function getWholeWeekDatePeriod(): ?array
    {
        return $this->wholeWeekDatePeriod;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventsWithTotalPlannedWorkingHours(): ?array
    {
        return $this->eventsWithTotalPlannedWorkingHours;
    }

    public function getTotalPlannedWorkingHours(): ?float
    {
        return $this->totalPlannedWorkingHours;
    }

    public function getRooms(): Collection|Closure|null
    {
        return $this->rooms;
    }

    /**
     * @return array<int, EventTypeResource>|null
     */
    public function getEventTypes(): array|Closure|null
    {
        return $this->eventTypes;
    }

    public function getProjects(): Collection|Closure|null
    {
        return $this->projects;
    }

    public function getShifts(): Collection|Closure|null
    {
        return $this->shifts;
    }

    public function getAvailabilities(): ?Collection
    {
        return $this->availabilities;
    }

    public function getShiftQualifications(): Collection|Closure|null
    {
        return $this->shiftQualifications;
    }

    public function getFirstProjectShiftTabId(): int|Closure|null
    {
        return $this->firstProjectShiftTabId;
    }

    public function getDaysWithData(): array|Closure|null
    {
        return $this->daysWithData;
    }

    public function getCrafts(): Collection|Closure|null
    {
        return $this->crafts;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'freelancer' => $this->getFreelancer(),
            'freelancer_to_edit_whole_week_date_period_vacations' => $this
                ->getFreelancerToEditWholeWeekDatePeriodVacations(),
            'calendarData' => $this->getCalendarData(),
            'dateToShow' => $this->getDateToShow(),
            'vacations' => $this->getVacations(),
            'vacationSelectCalendar' => $this->getVacationSelectCalendar(),
            'createShowDate' => $this->getCreateShowDate(),
            'showVacationsAndAvailabilitiesDate' => $this->getShowVacationsAndAvailabilitiesDate(),
            'dateValue' => $this->getDateValue(),
            'wholeWeekDatePeriod' => $this->getWholeWeekDatePeriod(),
            'eventsWithTotalPlannedWorkingHours' => $this->getEventsWithTotalPlannedWorkingHours(),
            'totalPlannedWorkingHours' => $this->getTotalPlannedWorkingHours(),
            'rooms' => $this->getRooms(),
            'eventTypes' => $this->getEventTypes(),
            'projects' => $this->getProjects(),
            'shifts' => $this->getShifts(),
            'availabilities' => $this->getAvailabilities(),
            'shiftQualifications' => $this->getShiftQualifications(),
            'firstProjectShiftTabId' => $this->getFirstProjectShiftTabId(),
            'daysWithData' => $this->getDaysWithData(),
            'crafts' => $this->getCrafts(),
        ];
    }
}
