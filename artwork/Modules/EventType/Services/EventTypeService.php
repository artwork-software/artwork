<?php

namespace Artwork\Modules\EventType\Services;

use Artwork\Modules\EventType\Repositories\EventTypeRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class EventTypeService
{
    public function __construct(private EventTypeRepository $eventTypeRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->eventTypeRepository->getAll();
    }
}
