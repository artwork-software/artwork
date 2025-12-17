<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\Project\Models\Project;

class ProjectTabMaterialIssueService
{
    public function buildMaterialIssuePayload(Project $project): array
    {
        $materials = InternalIssue::where('project_id', $project->id)
            ->with([
                'project',
                'articles.images',
                // 🔹 Tags der Artikel
                'articles.tags',
                // falls du auch Berechtigungen an den Tags brauchst:
                'articles.tags.allowedUsers',
                'articles.tags.allowedDepartments',
                'specialItems',
                'files',
                'responsibleUsers',
            ])
            ->get();

        return [
            'materials' => $materials,
            'first_event' => $project->events()->orderBy('start_time', 'ASC')->first(),
            'last_event' => $project->events()->orderBy('end_time', 'DESC')->first(),
            'materialSets' => MaterialSet::with('items.article', 'items.article.category', 'items.article.subCategory')->get(),
        ];
    }
}

