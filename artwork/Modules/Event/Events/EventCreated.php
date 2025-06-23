<?php

namespace Artwork\Modules\Event\Events;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Calendar\DTO\BroadcastEventDTO;
use Artwork\Modules\Calendar\DTO\BroadcastEventDTOWithVerifications;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\EventWithoutRoomDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class EventCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $event;
    public $roomId;

    public function __construct(
        Event $event,
        int $roomId
    ) {
        $this->event = $event;
        $this->roomId = $roomId;
    }

    public function broadcastAs()
    {
        return 'event.created';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('event.room.' . $this->roomId);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => $this->event->is_planning ? BroadcastEventDTOWithVerifications::formModel(
                $this->event,
            ) : BroadcastEventDTO::formModel(
                $this->event,
            ),
        ];
    }
}
