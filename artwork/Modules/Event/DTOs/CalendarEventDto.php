<?php

namespace Artwork\Modules\Event\DTOs;

use Artwork\Core\Abstracts\BaseDto;
use Illuminate\Support\Collection;

class CalendarEventDto extends BaseDto
{
    public ?Collection $areas = null;

    public ?Collection $projects = null;

    public ?Collection $eventTypes = null;

    public ?Collection $roomCategories = null;

    public ?Collection $roomAttributes = null;

    public ?Collection $events = null;

    public function setAreas(Collection $areas): self
    {
        $this->areas = $areas;

        return $this;
    }

    public function setProjects(Collection $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function setEventTypes(Collection $eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    public function setRoomCategories(Collection $roomCategories): self
    {
        $this->roomCategories = $roomCategories;

        return $this;
    }

    public function setRoomAttributes(Collection $roomAttributes): self
    {
        $this->roomAttributes = $roomAttributes;

        return $this;
    }

    public function setEvents(Collection $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function getAreas(): ?Collection
    {
        return $this->areas;
    }

    public function getProjects(): ?Collection
    {
        return $this->projects;
    }

    public function getEventTypes(): ?Collection
    {
        return $this->eventTypes;
    }

    public function getRoomCategories(): ?Collection
    {
        return $this->roomCategories;
    }

    public function getRoomAttributes(): ?Collection
    {
        return $this->roomAttributes;
    }

    public function getEvents(): ?Collection
    {
        return $this->events;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $property => $value) {
            $result[$property] = $value;
        }

        return $result;
    }
}
