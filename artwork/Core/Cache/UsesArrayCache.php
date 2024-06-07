<?php

namespace Artwork\Core\Cache;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Collection;

trait UsesArrayCache
{
    protected static ?Collection $items = null;

    public static function forgetAll(): void
    {
        static::$items = new Collection();
    }

    public static function getAll(): ?Collection
    {
        return static::$items;
    }

    public static function setAll(Collection $items): void
    {
        static::$items = $items;
    }

    public static function setItem(Model $item): void
    {
        if (!static::$items) {
            static::$items = new Collection();
        }

        static::$items->add($item);
    }

    public static function getItem(int $itemId): ?Model
    {
        if (!static::$items) {
            static::$items = new Collection();
        }

        if (!$item = static::$items->filter(fn(Model $item) => $item->id === $itemId)->first()) {
            /** @var ServiceWithArrayCache $service */
            $service = app()->get(static::$service);
            $item = $service->findByIdWithoutCache($itemId);
        }

        return $item;
    }

    public static function getItemByName(string $name): ?Model
    {
        if (!static::$items) {
            static::$items = new Collection();
        }

        if (!$item = static::$items->filter(fn(Model $item) => $item->name === $name)->first()) {
            /** @var ServiceWithArrayCache $service */
            $service = app()->get(static::$service);
            $item = $service->findByNameWithoutCache($name);
        }
        return $item;
    }
}
