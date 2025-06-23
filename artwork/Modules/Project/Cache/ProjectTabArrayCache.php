<?php

namespace Artwork\Modules\Project\Cache;

use Artwork\Core\Cache\ArrayCache;
use Artwork\Core\Cache\UsesArrayCache;
use Artwork\Modules\Project\Services\ProjectTabService;

class ProjectTabArrayCache implements ArrayCache
{
    use UsesArrayCache;

    protected static string $service = ProjectTabService::class;
}
