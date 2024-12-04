<?php

namespace Artwork\Core\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

class CollectionService
{
    public function getSupportCollection(array $attributes = []): SupportCollection
    {
        return SupportCollection::make($attributes);
    }

    public function getEloquentCollection(array $attributes = []): EloquentCollection
    {
        return EloquentCollection::make($attributes);
    }
}
