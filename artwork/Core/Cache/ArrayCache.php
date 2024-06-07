<?php

namespace Artwork\Core\Cache;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Collection;

interface ArrayCache
{
    public static function forgetAll(): void;

    public static function getAll(): ?Collection;

    public static function setAll(Collection $items): void;

    public static function setItem(Model $item): void;

    public static function getItem(int $itemId): ?Model;

    public static function getItemByName(string $name): ?Model;
}
