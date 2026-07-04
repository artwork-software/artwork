<?php

namespace Artwork\Modules\Shift\Events;

use Illuminate\Broadcasting\PrivateChannel;

trait PushesShiftModification
{
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('shifts');
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // Payload stabil halten: diese Relationen kamen früher über das globale $with
        // des Shift-Models und werden von den Live-Update-Konsumenten im Frontend erwartet
        $this->shift->loadMissing(['craft', 'users', 'freelancer', 'serviceProvider', 'committedBy']);
        $event = $this->shift->event;
        // Schichten ohne Event (z. B. Raum-Schichten) dürfen den Broadcast nicht crashen
        return array_merge($this->shift->toArray(), ['event' => $event?->toArray()]);
    }
}
