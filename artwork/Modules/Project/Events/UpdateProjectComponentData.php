<?php

namespace Artwork\Modules\Project\Events;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\ProjectComponentValue;
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
class UpdateProjectComponentData implements ShouldBroadcastNow, ShouldDispatchAfterCommit
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

    /**
     * Nur Kennungen: Der Kanal project.{id} prüft nur das Projekt-Sichtrecht. Vorher ging der volle
     * Wert auch eingeschränkter Komponenten (permission_type, Tab-Sichtbarkeit) an alle
     * Projektsichtigen. Der Client lädt den Wert über project.tab.component.value nach.
     *
     * @return array{data: array{id: int, project_id: int, component_id: int}}
     */
    public function broadcastWith(): array
    {
        return [
            'data' => [
                'id' => $this->data->id,
                'project_id' => $this->data->project_id,
                'component_id' => $this->data->component_id,
            ],
        ];
    }
}
