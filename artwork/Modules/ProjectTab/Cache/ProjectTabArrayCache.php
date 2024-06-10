<?php

namespace Artwork\Modules\ProjectTab\Cache;

use Artwork\Core\Cache\ArrayCache;
use Artwork\Core\Cache\UsesArrayCache;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;

class ProjectTabArrayCache implements ArrayCache
{
    use UsesArrayCache;

    protected static string $service = ProjectTabService::class;
}
