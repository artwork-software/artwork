<?php

namespace Artwork\Modules\IndividualTimes\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IndividualTimeChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int $workerId
     * @param int $workerType 0=User, 1=Freelancer, 2=ServiceProvider
     */
    public function __construct(
        public readonly int $workerId,
        public readonly int $workerType,
    ) {
    }

    public function broadcastAs(): string
    {
        return 'individual-time.changed';
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('shift-plan.individual-times'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return [
            'workerId' => $this->workerId,
            'workerType' => $this->workerType,
        ];
    }
}
