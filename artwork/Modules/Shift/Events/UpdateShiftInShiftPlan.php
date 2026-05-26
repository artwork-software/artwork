<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateShiftInShiftPlan implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $shift;
    public $roomId;

    public function __construct(Shift $shift, int $roomId)
    {
        $this->shift = $shift;
        $this->roomId = $roomId;
    }

    public function broadcastAs()
    {
        return 'shift-created';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('shift-plan.room.' . $this->roomId);
    }

    public function broadcastWith(): array
    {
        $this->shift->loadMissing([
            'shiftsQualifications',
            'globalQualifications',
            'users.globalQualifications',
            'freelancer.globalQualifications',
            'serviceProvider.globalQualifications',
            'project',
        ]);

        return [
            'shift' => ShiftDTO::fromModel($this->shift, $this->shift->project),
            'roomId' => $this->roomId,
        ];
    }
}
