<?php

namespace Artwork\Modules\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectDayAssignmentsChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly int $projectId)
    {
    }

    public function broadcastAs(): string
    {
        return 'project-day-assignments.changed';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.' . $this->projectId);
    }

    /**
     * @return array{project_id: int}
     */
    public function broadcastWith(): array
    {
        return ['project_id' => $this->projectId];
    }
}
