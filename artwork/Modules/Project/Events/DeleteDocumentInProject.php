<?php

namespace Artwork\Modules\Project\Events;

use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeleteDocumentInProject implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $projectFile;
    public $projectId;

    public function __construct(ProjectFile $projectFile, int $projectId)
    {
        $this->projectFile = $projectFile;
        $this->projectId = $projectId;
    }

    public function broadcastAs()
    {
        return 'document.delete';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.' . $this->projectId);
    }

    public function broadcastWith(): array
    {
        return [
            'document' => $this->projectFile,
        ];
    }
}