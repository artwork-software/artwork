<?php

namespace Artwork\Modules\EventType\Services;

use Artwork\Core\Cache\ServiceWithArrayCache;
use Artwork\Modules\EventType\Cache\EventTypeArrayCache;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Repositories\EventTypeRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class EventTypeService implements ServiceWithArrayCache
{
    public function __construct(private EventTypeRepository $eventTypeRepository)
    {
    }

    public function findById(int $id): ?EventType
    {
        if (!$eventType = EventTypeArrayCache::getItem($id)) {
            $eventType = $this->findByIdWithoutCache($id);
            EventTypeArrayCache::setItem($eventType->id, $eventType);
        }
        return $eventType;
    }

    public function findByIdWithoutCache(int $id): ?EventType
    {
       return $this->eventTypeRepository->getById($id);
    }

    public function getAll(): Collection
    {
        if (!$all = EventTypeArrayCache::getAll()) {
            $all = $this->eventTypeRepository->getAll();
            EventTypeArrayCache::setAll($all);
        }
        return $all;
    }

    public function findByNameWithoutCache(?string $name): ?EventType
    {
        if (!$name) {
            return null;
        }
        return $this->eventTypeRepository->getByName($name);
    }

    public function save(EventType $eventType): EventType
    {
        return $this->eventTypeRepository->save($eventType);
    }

    public function findByName(?string $name): ?EventType
    {
        if (!$name) {
            return null;
        }
        if (!$eventType = EventTypeArrayCache::getItemByName($name)) {
            if ($eventType = $this->findByNameWithoutCache($name)) {
                EventTypeArrayCache::setItem($eventType);
            }
        }
        return $eventType;
    }
}
