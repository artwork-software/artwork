<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Repositories\EventPropertyRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
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

    public function find(int $id): EventProperty|null
    {
        /** @var EventProperty $eventProperty */
        $eventProperty = $this->eventPropertyRepository->find($id);

        return $eventProperty;
    }

    /**
     * @throws Throwable
     */
    public function findOrFailById(int $id): EventProperty|null
    {
        /** @var EventProperty $eventProperty */
        $eventProperty = $this->eventPropertyRepository->findOrFail($id);

        return $eventProperty;
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

    public function updateFromRequest(
        EventProperty $eventProperty,
        Request $request
    ): EventProperty {
        /**
         * @var EventProperty $eventProperty
         */
        $eventProperty = $this->eventPropertyRepository->save($eventProperty->fill($request->all()));

        return $eventProperty;
    }

    public function createFromRequest(Request $request): EventProperty
    {
        /**
         * @var EventProperty $eventProperty
         */
        $eventProperty = $this->eventPropertyRepository->save(
            $this->eventPropertyRepository->getNewModelInstance()->fill($request->all())
        );

        return $eventProperty;
    }

    public function forceDelete(EventProperty $eventProperty): void
    {
        $this->eventPropertyRepository->forceDelete($eventProperty);
    }
}
