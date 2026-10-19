<?php

namespace Artwork\Modules\Project\Events;

use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Läuft in einer DB-Transaktion erst nach dem Commit (ShouldDispatchAfterCommit), damit Clients beim
 * Nachladen den gespeicherten Stand bekommen.
 */
class UploadNewDocumentInProject implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $projectFile;
    public $projectId;

    /**
     * @param bool $visibilityChanged "Sichtbar in" geändert: alle Upload-Bereiche laden nach (auch die, aus denen
     *        die Datei verschwindet)
     */
    public function __construct(ProjectFile $projectFile, int $projectId, public bool $visibilityChanged = false)
    {
        $this->projectFile = $projectFile;
        $this->projectId = $projectId;
    }

    public function broadcastAs()
    {
        return 'document.add';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.' . $this->projectId);
    }

    /**
     * Nur Ids: der Kanal project.{id} erreicht alle Projektsichtigen, auch ohne Sicht auf den Tab
     * oder die Budget-Freigabe der Datei. Die Listen laden über ihre geprüften Endpunkte nach.
     *
     * @return array{document: array{id: int, tab_id: int|null, project_id: int, visible_in: list<string>|null, visibility_changed: bool}}
     */
    public function broadcastWith(): array
    {
        return [
            'document' => [
                'id' => $this->projectFile->id,
                'tab_id' => $this->projectFile->tab_id,
                'project_id' => $this->projectId,
                // Upload-Bereiche (Schlüssel) – keine Inhalte
                'visible_in' => $this->projectFile->visible_in,
                'visibility_changed' => $this->visibilityChanged,
            ],
        ];
    }
}
