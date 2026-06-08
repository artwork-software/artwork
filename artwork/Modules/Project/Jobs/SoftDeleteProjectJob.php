<?php

namespace Artwork\Modules\Project\Jobs;

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
 * Soft-deletes a project together with its whole cascade (events, shifts, sub-events,
 * timelines, comments, budget table, ...) in the background.
 *
 * Projects with very many events (e.g. 10k) would otherwise blow the PHP/HTTP timeout
 * when deleted synchronously from the request, so the heavy work is queued here.
 */
class SoftDeleteProjectJob implements ShouldQueue
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

    public function handle(ProjectService $projectService): void
    {
        // The controller already soft-deletes the project row for instant UI feedback, so we
        // must include trashed here – otherwise find() would skip it and the cascade (events,
        // shifts, ...) would never run.
        $project = Project::withTrashed()->find($this->projectId);

        // Already hard-deleted in the meantime – nothing to do.
        if ($project === null) {
            return;
        }

        // Queue-Jobs haben keinen auth()-User; ohne expliziten Causer würden alle
        // kaskadierten Lösch-Activities (Schichten etc.) als "System" geloggt. Den
        // auslösenden User setzen, damit der Schichtverlauf zeigt, wer gelöscht hat.
        $this->resolveCauser();

        // Resolve softDelete's many service dependencies from the container while
        // binding the project explicitly.
        app()->call([$projectService, 'softDelete'], ['project' => $project]);
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
