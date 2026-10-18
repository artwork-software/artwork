<?php

namespace Artwork\Modules\Event\Events;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoveEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $event;
    public $roomId;

    /**
     * @param int|null $roomId Kanal des Raums; Termine ohne Raum haben keinen Raumkanal
     */
    public function __construct(Event $event, ?int $roomId)
    {
        $this->event = $event;
        $this->roomId = $roomId;
    }

    public function broadcastWhen(): bool
    {
        return $this->roomId !== null;
    }

    public function broadcastAs()
    {
        return 'event.removed';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('event.room.' . $this->roomId);
    }

    public function broadcastWith(): array
    {
        // Zum Entfernen genügt die Kennung – der volle Termin ging auf dem offenen Raumkanal auch bei geplanten
        // Terminen an alle (Planungskalender-Recht)
        return [
            'event' => [
                'id' => $this->event->id,
                'roomId' => $this->roomId,
            ],
        ];
    }
}
