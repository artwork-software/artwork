<?php

namespace Artwork\Modules\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateProjectContractsDocuments implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public int $projectId;

    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
    }

    public function broadcastAs(): string
    {
        return 'contracts-documents.updated';
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
