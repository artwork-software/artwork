<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

class ShiftAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly User $user,
        public readonly Shift $shift,
    ) {
    }

    public function broadcastAs()
    {
        return 'shift.assigned';
    }

    public function broadcastOn()
    {
        return new PrivateChannel('shifts');
    }

    public function broadcastWith(): array
    {
        $event = $this->shift->event;
        $eventStudlyCase = [];

        if ($event !== null) {
            foreach ($event->toArray() as $key => $value) {
                $eventStudlyCase[lcfirst(Str::studly($key))] = $value;
            }
        }

        return array_merge($this->shift->toArray(), ['user' => $this->user->toArray(), 'event' => $eventStudlyCase]);
    }
}
