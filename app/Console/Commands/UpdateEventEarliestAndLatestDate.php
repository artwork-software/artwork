<?php

namespace App\Console\Commands;

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
            $event->update([
                'earliest_start_datetime' => $eventService->getEarliestStartTime($event),
                'latest_end_datetime' => $eventService->getLatestEndTime($event),
            ]);
            $this->info("Updated event {$event->id}");
        }
    }
}
