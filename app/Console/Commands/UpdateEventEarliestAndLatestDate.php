<?php

namespace App\Console\Commands;

use Artwork\Modules\Event\Services\EventService;
use Illuminate\Console\Command;

class UpdateEventEarliestAndLatestDate extends Command
{
    protected $signature = 'app:update-event-earliest-and-latest-date';

    protected $description = 'Command description';

    public function handle(
        EventService $eventService
    ): void {
        foreach ($eventService->getAll() as $event) {
            $event->touchQuietly(); // touch() has implicit save call
            $eventService->save($event);
            $this->info("Updated event {$event->id}");
        }
    }
}
