<?php

namespace Artwork\Modules\EventType\Services;

use Artwork\Modules\EventType\Cache\EventTypeArrayCache;
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
        if (!$eventType = EventTypeArrayCache::getItem($id)) {
            $eventType = $this->findByIdWithoutCache($id);
            EventTypeArrayCache::setItem($eventType);
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

    public function getFallbackEventType(): ?EventType
    {
        if (!$fallbackType = $this->eventTypeRepository->getFallbackEventType()) {
            $fallbackType = new EventType();
            $fallbackType->name = 'Fallback EventType';
            $fallbackType->hex_code = '#ff00ff';
            $fallbackType->project_mandatory = false;
            $fallbackType->individual_name = false;
            $fallbackType->abbreviation = 'FB';
            $fallbackType->relevant_for_shift = false;
            $fallbackType->fallback_type = true;
            $this->save($fallbackType);
        }

        return $fallbackType;
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
