<?php

namespace App\Console\Commands;

use Artwork\Modules\Event\Events\UpdateEventEarliestLatestDates;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Illuminate\Console\Command;

class UpdateEventEarliestAndLatestDate extends Command
{
    protected $signature = 'app:update-event-earliest-and-latest-date';

    protected $description = 'Command description';

    public function handle(
        EventService $eventService
    ): void {
        $events = Event::all();
        foreach ($events as $event) {
            event(new UpdateEventEarliestLatestDates($event, $eventService));
            $this->info("Updated event {$event->id}");
        }
    }
}
