<?php

namespace Artwork\Modules\Craft;

use Artwork\Modules\EventType\Repositories\EventTypeRepository;
use Illuminate\Database\Eloquent\Collection;

class EventTypeService
{
    public function __construct(private readonly EventTypeRepository $eventTypeRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->eventTypeRepository->getAll();
    }
}
