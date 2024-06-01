<?php

namespace Artwork\Modules\EventType\Services;

use Artwork\Modules\EventType\Cache\EventTypeMemoryCache;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Repositories\EventTypeRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class EventTypeService
{
    public function __construct(private EventTypeRepository $eventTypeRepository)
    {
    }

    public function findById(int $id): ?EventType
    {
        if (!$eventType = EventTypeMemoryCache::getEventType($id)) {
            $eventType = $this->findByIdWithoutCache($id);
            EventTypeMemoryCache::setEventType($eventType->id, $eventType);
        }
        return $eventType;
    }

    public function findByIdWithoutCache(int $id): ?EventType
    {
        $eventType = $this->eventTypeRepository->getById($id);
        return $eventType;
    }

    public function getAll(): Collection
    {
        if (!$all = EventTypeMemoryCache::getAll()) {
            $all = $this->eventTypeRepository->getAll();
            EventTypeMemoryCache::setAll($all);
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
        if (!$eventType = EventTypeMemoryCache::getEventTypeByName($name)) {
            if ($eventType = $this->findByNameWithoutCache($name)) {
                EventTypeMemoryCache::setEventType($eventType);
            }
        }
        return $eventType;
    }
}
