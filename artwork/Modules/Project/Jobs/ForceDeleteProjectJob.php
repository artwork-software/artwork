<?php

namespace Artwork\Modules\Project\Jobs;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Activitylog\Facades\CauserResolver;

/**
 * Permanently deletes a previously trashed project together with its whole cascade
 * (events, shifts, sub-events, timelines, comments, budget table, ...) in the background.
 *
 * Like the soft delete, this would blow the PHP/HTTP timeout for projects with very many
 * events when run synchronously from the request, so the heavy work is queued here.
 */
class ForceDeleteProjectJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(private readonly int $projectId, private readonly ?int $causerId = null)
    {
    }

    public function handle(EventService $eventService, ProjectService $projectService): void
    {
        $project = Project::onlyTrashed()->find($this->projectId);

        // Not trashed (anymore) or already gone – nothing to do.
        if ($project === null) {
            return;
        }

        // Queue-Jobs haben keinen auth()-User; ohne expliziten Causer würden alle
        // kaskadierten Lösch-Activities (Schichten etc.) als "System" geloggt. Den
        // auslösenden User setzen, damit der Schichtverlauf zeigt, wer gelöscht hat.
        $this->resolveCauser();

        $events = Event::onlyTrashed()->where('project_id', $this->projectId)->get();

        // Resolve the remaining service dependencies from the container while binding the
        // already-loaded models explicitly.
        app()->call([$eventService, 'forceDeleteAll'], ['events' => $events]);
        app()->call([$projectService, 'forceDelete'], ['project' => $project]);
    }

    private function resolveCauser(): void
    {
        if ($this->causerId === null) {
            return;
        }

        $causer = User::find($this->causerId);

        if ($causer !== null) {
            CauserResolver::setCauser($causer);
        }
    }
}
