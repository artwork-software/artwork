<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectTabDocumentService
{
    public function buildDocumentPayload(Project $project, ?ComponentInTab $componentInTab = null): array
    {
        $documents = $this->loadDocuments($project, $componentInTab?->scope);

        return [
            'documents' => $documents,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
        ];
    }

    public function buildAllDocumentsPayload(Project $project): array
    {
        return [
            'documents' => $project->project_files,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
        ];
    }

    private function loadDocuments(Project $project, ?array $scope): Collection
    {
        if (empty($scope)) {
            return new Collection();
        }

        return $project->project_files()
            ->whereIn('tab_id', $scope)
            ->get();
    }
}


