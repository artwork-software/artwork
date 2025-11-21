<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;

class ProjectTabStatusService
{
    public function buildStatusPayload(Project $project): array
    {
        return [
            'state' => ProjectState::find($project->state),
        ];
    }
}

