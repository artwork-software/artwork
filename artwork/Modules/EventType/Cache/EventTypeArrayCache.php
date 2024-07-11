<?php

namespace Artwork\Modules\EventType\Cache;

use Artwork\Core\Cache\ArrayCache;
use Artwork\Core\Cache\UsesArrayCache;
use Artwork\Modules\EventType\Services\EventTypeService;

class EventTypeArrayCache implements ArrayCache
{
    use UsesArrayCache;

    protected static string $service = EventTypeService::class;
}
