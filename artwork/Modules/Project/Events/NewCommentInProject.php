<?php

namespace Artwork\Modules\Project\Events;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Erst nach dem Commit senden: Clients laden auf das Event hin über geprüfte Endpunkte nach und
 * würden sonst den Stand vor der Änderung lesen.
 */
class NewCommentInProject implements ShouldBroadcastNow, ShouldDispatchAfterCommit
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

    /**
     * Nur Kennungen: Der Kanal project.{id} prüft nur das Projekt-Sichtrecht, nicht die
     * Tab-Sichtbarkeit. Die Clients laden die betroffene Kommentarliste über die geprüften
     * Endpunkte (projects.tabs.all-comments / projects.tabs.comments) nach – vorher lagen
     * Text und Autor:in jedes neuen Kommentars bei allen Projektsichtigen.
     *
     * @return array{comment: array{id: int, project_id: int|null, tab_id: int|null}}
     */
    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'id' => $this->comment->id,
                'project_id' => $this->comment->project_id,
                'tab_id' => $this->comment->tab_id,
            ],
        ];
    }
}
