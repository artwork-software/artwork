<?php

namespace Artwork\Modules\EventProperty\Services;

use Artwork\Modules\EventProperty\Models\EventProperty;
use Artwork\Modules\EventProperty\Repositories\EventPropertyRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Throwable;

class EventPropertyService
{
    public function __construct(private readonly EventPropertyRepository $eventPropertyRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->eventPropertyRepository->getAll();
    }

    public function getIdsOfAllExisting(): SupportCollection
    {
        return $this->getAll()->pluck('id');
    }

    /**
     * @throws Throwable
     */
    public function create(array $attributes): EventProperty
    {
        /** @var EventProperty $eventProperty */
        $eventProperty = $this->eventPropertyRepository->saveOrFail(
            $this->eventPropertyRepository->getNewModelInstance()->fill($attributes)
        );

        return $eventProperty;
    }

    public function update(): EventProperty
    {
    }

    public function updateEventProperties(): void
    {

    }

    public function createFromRequest(): EventProperty
    {
    }
}
