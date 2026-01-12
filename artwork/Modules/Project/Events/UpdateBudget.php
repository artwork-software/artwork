<?php

namespace Artwork\Modules\Project\Events;

use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateBudget implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $projectId;

    public function __construct(int $projectId = null)
    {
        if ($projectId === null) {
            return;
        }
        $this->projectId = $projectId;
    }

    public function broadcastAs()
    {
        return 'budget.update';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.' . $this->projectId);
    }

    public function broadcastWith(): array
    {
        return [
            'projectId' => $this->projectId,
        ];
    }
}
