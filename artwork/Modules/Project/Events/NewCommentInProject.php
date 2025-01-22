<?php

namespace Artwork\Modules\Project\Events;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCommentInProject implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $comment;
    public $projectId;

    public function __construct(Comment $comment, int $projectId)
    {
        $this->comment = $comment;
        $this->projectId = $projectId;
    }

    public function broadcastAs()
    {
        return 'comment.add';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.' . $this->projectId);
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => $this->comment,
        ];
    }
}