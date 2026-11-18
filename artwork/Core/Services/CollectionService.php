<?php

namespace Artwork\Core\Services;

use Illuminate\Support\Collection as SupportCollection;

class CollectionService
{
    public function getSupportCollection(array $attributes = []): SupportCollection
    {
        return SupportCollection::make($attributes);
    }
}
