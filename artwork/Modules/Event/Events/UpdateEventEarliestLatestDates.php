<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateEventEarliestLatestDates
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Event $event,
        private readonly EventService $eventService
    ) {
        $event->update([
            'earliest_start_datetime' => $this->eventService->getEarliestStartTime($event),
            'latest_end_datetime' => $this->eventService->getLatestEndTime($event),
        ]);
    }
}
