<?php

namespace Artwork\Modules\Ticketing\Providers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Observers\TicketingEventObserver;
use Illuminate\Support\ServiceProvider;

class TicketingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::observe(TicketingEventObserver::class);
    }
}
