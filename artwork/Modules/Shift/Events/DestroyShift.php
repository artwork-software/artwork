<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DestroyShift implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Shift $shift;
    public int $roomId;
    public array $affectedWorkers;

    public function __construct(Shift $shift, int $roomId, array $affectedWorkers = [])
    {
        $this->shift = $shift;
        $this->roomId = $roomId;
        $this->affectedWorkers = $affectedWorkers;
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'shift-destroyed.in.event';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('destroy.events.room.' . $this->roomId);
    }

    public function broadcastWith(): array
    {
        $this->shift->load([
            'shiftsQualifications',
            'globalQualifications',
            'users.globalQualifications',
            'freelancer.globalQualifications',
            'serviceProvider.globalQualifications',
            'project',
        ]);

        $shiftData = ShiftDTO::fromModel($this->shift, $this->shift->project);

        // If workers were collected before detach, inject them into the DTO
        if (!empty($this->affectedWorkers) && empty($shiftData->workers)) {
            $shiftData->workers = $this->affectedWorkers;
        }

        return [
            'shift' => $shiftData,
            'roomId' => $this->roomId,
        ];
    }


}