<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;

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

    /**
     * Schlanker Payload (ShiftDTO, Worker nur mit id/type/name/Pivot): das User-Model trägt
     * Gehalts- und Kontaktdaten, die nicht über Broadcast laufen dürfen.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->shift->loadMissing([
            'craft:id,name,abbreviation,color',
            'shiftsQualifications',
            'globalQualifications',
            'users.globalQualifications',
            'freelancer.globalQualifications',
            'serviceProvider.globalQualifications',
            'project',
            'event',
        ]);

        $event = $this->shift->event;

        return [
            'shift' => ShiftDTO::fromModel($this->shift, $this->shift->project),
            'roomId' => $this->shift->room_id ?? $event?->room_id,
            'user' => [
                'id' => $this->user->id,
                'type' => 'user',
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ],
            'event' => $event === null ? null : [
                'id' => $event->id,
                'roomId' => $event->room_id,
                'projectId' => $event->project_id,
            ],
        ];
    }
}
