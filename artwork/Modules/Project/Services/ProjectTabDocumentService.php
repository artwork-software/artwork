<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
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

    public function buildAllDocumentsPayload(Project $project, ?User $user = null): array
    {
        $documents = $this->loadVisibleDocuments($project, $user);
        $hiddenTabNames = $this->getHiddenTabNames($project, $user);

        return [
            'documents' => $documents,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'hiddenTabNames' => $hiddenTabNames,
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

    private function loadVisibleDocuments(Project $project, ?User $user): Collection
    {
        if (!$user) {
            return $project->project_files;
        }

        $visibleTabIds = ProjectTab::query()->visibleForUser($user)->pluck('id');

        return $project->project_files()
            ->where(function ($query) use ($visibleTabIds) {
                $query->whereIn('tab_id', $visibleTabIds)
                    ->orWhereNull('tab_id');
            })
            ->get();
    }

    /**
     * @return string[]
     */
    private function getHiddenTabNames(Project $project, ?User $user): array
    {
        if (!$user) {
            return [];
        }

        $visibleTabIds = ProjectTab::query()->visibleForUser($user)->pluck('id');

        $hiddenTabIdsWithDocuments = $project->project_files()
            ->whereNotNull('tab_id')
            ->whereNotIn('tab_id', $visibleTabIds)
            ->distinct()
            ->pluck('tab_id');

        if ($hiddenTabIdsWithDocuments->isEmpty()) {
            return [];
        }

        return ProjectTab::query()
            ->whereIn('id', $hiddenTabIdsWithDocuments)
            ->pluck('name')
            ->toArray();
    }
}


