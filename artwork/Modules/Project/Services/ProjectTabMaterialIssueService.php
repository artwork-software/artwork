<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Project\Models\Project;

class ProjectTabMaterialIssueService
{
    public function buildMaterialIssuePayload(Project $project): array
    {
        $materials = InternalIssue::where('project_id', $project->id)
            ->with(['articles.images', 'specialItems', 'files', 'responsibleUsers'])
            ->get();

        return [
            'materials' => $materials,
            'first_event' => $project->events()->orderBy('start_time', 'ASC')->first(),
            'last_event' => $project->events()->orderBy('end_time', 'DESC')->first(),
        ];
    }
}

