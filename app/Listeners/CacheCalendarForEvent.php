<?php

namespace App\Listeners;

use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Events\EventSavedForCalendarCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CacheCalendarForEvent implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct(
        private CalendarService $calendarService
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(EventSavedForCalendarCache $event): void
    {
        $this->calendarService->updateCalendarCacheForEvent($event->event, $event->user);
    }
}
