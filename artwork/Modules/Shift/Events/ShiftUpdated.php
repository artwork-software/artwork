<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Shift\Models\Shift;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;

class ShiftUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use PushesShiftModification;

    public function __construct(private readonly Shift $shift)
    {
    }

    public function broadcastAs()
    {
        return 'shift.updated';
    }
}
