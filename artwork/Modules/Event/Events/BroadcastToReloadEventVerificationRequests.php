<?php

namespace Artwork\Modules\Event\Events;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Calendar\DTO\BroadcastEventDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\EventWithoutRoomDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BroadcastToReloadEventVerificationRequests implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public User $user;

    public function __construct(
        User $user
    ) {
        $this->user = $user;
    }

    public function broadcastAs()
    {
        return 'reload-event-verification-requests';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('event-verification-index.' . $this->user->id);
    }

}
