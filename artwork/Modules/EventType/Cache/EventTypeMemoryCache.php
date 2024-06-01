<?php

namespace Artwork\Modules\EventType\Cache;

use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
use Illuminate\Database\Eloquent\Collection;

class EventTypeMemoryCache
{
    protected static ?Collection $eventTypes = null;

    public static function forgetAll(): void
    {
        static::$eventTypes = null;
    }

    public static function getAll(): ?Collection
    {
        return static::$eventTypes;
    }

    public static function setAll(Collection $eventTypes): void
    {
        static::$eventTypes = $eventTypes;
    }

    public static function getEventType(int $eventTypeId): ?EventType
    {
        if (!$eventType = static::$eventTypes->filter(fn(EventType $eventType) => $eventType->id === $eventTypeId)->first()) {
            /** @var EventTypeService $eventTypeService */
            $eventTypeService = app()->get(EventTypeService::class);
            $eventType = $eventTypeService->findByIdWithoutCache($eventTypeId);
        }

        return $eventType;
    }

    public static function setEventType(EventType $eventType): void
    {
        static::$eventTypes->add($eventType);
    }

    public static function getEventTypeByName(string $name): ?EventType
    {
        if (!$eventType = static::$eventTypes->filter(fn(EventType $eventType) => $eventType->name === $name)->first()) {
            /** @var EventTypeService $eventTypeService */
            $eventTypeService = app()->get(EventTypeService::class);
            $eventType = $eventTypeService->findByNameWithoutCache($name);
        }
        return $eventType;
    }
}
