<?php

namespace Artwork\Modules\ServiceProvider\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShowResource;
use Illuminate\Database\Eloquent\Collection;

class ShowDto extends BaseDto
{
    public ?ServiceProviderShowResource $serviceProvider = null;

    public ?array $dateValue = null;

    public ?array $wholeWeekDatePeriod = null;

    public ?array $eventsWithTotalPlannedWorkingHours = null;

    public ?float $totalPlannedWorkingHours = null;

    public ?Collection $rooms = null;

    public ?Collection $eventTypes = null;

    public ?Collection $projects = null;

    public ?Collection $shifts = null;

    public ?Collection $shiftQualifications = null;

    public ?int $firstProjectShiftTabId = null;

    public function setServiceProvider(?ServiceProviderShowResource $serviceProvider): self
    {
        $this->serviceProvider = $serviceProvider;

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

    public function setRooms(?Collection $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function setEventTypes(?Collection $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setProjects(?Collection $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function setShifts(?Collection $shifts): self
    {
        $this->shifts = $shifts;

        return $this;
    }

    public function setShiftQualifications(?Collection $shiftQualifications): self
    {
        $this->shiftQualifications = $shiftQualifications;

        return $this;
    }

    public function setFirstProjectShiftTabId(?int $firstProjectShiftTabId): self
    {
        $this->firstProjectShiftTabId = $firstProjectShiftTabId;

        return $this;
    }

    public function getServiceProvider(): ?ServiceProviderShowResource
    {
        return $this->serviceProvider;
    }

    /**
     * @return array<int, string>|null
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

    public function getRooms(): ?Collection
    {
        return $this->rooms;
    }

    public function getEventTypes(): ?Collection
    {
        return $this->eventTypes;
    }

    public function getProjects(): ?Collection
    {
        return $this->projects;
    }

    public function getShifts(): ?Collection
    {
        return $this->shifts;
    }

    public function getShiftQualifications(): ?Collection
    {
        return $this->shiftQualifications;
    }

    public function getFirstProjectShiftTabId(): ?int
    {
        return $this->firstProjectShiftTabId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'serviceProvider' => $this->getServiceProvider(),
            'dateValue' => $this->getDateValue(),
            'wholeWeekDatePeriod' => $this->getWholeWeekDatePeriod(),
            'eventsWithTotalPlannedWorkingHours' => $this->getEventsWithTotalPlannedWorkingHours(),
            'totalPlannedWorkingHours' => $this->getTotalPlannedWorkingHours(),
            'rooms' => $this->getRooms(),
            'eventTypes' => $this->getEventTypes(),
            'projects' => $this->getProjects(),
            'shifts' => $this->getShifts(),
            'shiftQualifications' => $this->getShiftQualifications(),
            'firstProjectShiftTabId' => $this->getFirstProjectShiftTabId(),
        ];
    }
}
