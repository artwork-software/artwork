<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;

class ProjectTabCommentService
{
    public function buildCommentPayload(Project $project, ?ComponentInTab $componentInTab = null): array
    {
        $comments = $this->loadComments($project, $componentInTab?->scope);

        return [
            'comments' => $comments,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
        ];
    }

    private function loadComments(Project $project, ?array $scope)
    {
        $query = $project->comments()
            ->with('user')
            ->orderBy('created_at', 'DESC');

        if (!empty($scope)) {
            $query->whereIn('tab_id', $scope);
        }

        return $query->get();
    }

    public function buildAllCommentsPayload(Project $project): array
    {
        $comments = $project->comments()
            ->with('user')
            ->orderBy('created_at', 'DESC')
            ->get();

        return [
            'comments' => $comments,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
        ];
    }
}
