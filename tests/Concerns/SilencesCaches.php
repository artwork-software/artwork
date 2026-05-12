<?php

namespace Tests\Concerns;

use Artwork\Modules\EventType\Cache\EventTypeArrayCache;
use Artwork\Modules\Project\Cache\ProjectTabArrayCache;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

trait SilencesCaches
{
    protected function silenceCaches(): void
    {
        EventTypeArrayCache::forgetAll();
        ProjectTabArrayCache::forgetAll();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
