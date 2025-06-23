<?php

namespace Artwork\Modules\Project\Events;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateProjectComponentData implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $data;
    public $projectId;

    public function __construct(ProjectComponentValue $data, int $projectId)
    {
        $this->data = $data;
        $this->projectId = $projectId;
    }

    public function broadcastAs()
    {
        return 'data.updated';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.' . $this->projectId);
    }

    public function broadcastWith(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}