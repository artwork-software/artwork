<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;

class ProjectTabCommentService
{
    public function __construct(
        private readonly ProjectComponentVisibilityService $projectComponentVisibilityService,
    ) {
    }

    public function buildCommentPayload(
        Project $project,
        ?ComponentInTab $componentInTab = null,
        ?User $user = null
    ): array {
        $comments = $this->loadComments($project, $componentInTab?->scope, $user);

        return [
            'comments' => $comments,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
        ];
    }

    private function loadComments(Project $project, ?array $scope, ?User $user)
    {
        $query = $project->comments()
            ->with('user')
            ->orderBy('created_at', 'DESC');

        if (!empty($scope)) {
            // Tab-Auswahl der Komponente, ohne Tabs, die die Person nicht sehen darf
            $query->whereIn(
                'tab_id',
                $user !== null
                    ? $this->projectComponentVisibilityService->restrictScopeToVisibleTabs($user, $scope)
                    : $scope
            );
        } elseif ($user !== null) {
            $this->projectComponentVisibilityService->constrainToVisibleTabs($query, $user);
        }

        return $query->get();
    }

    public function buildAllCommentsPayload(Project $project, ?User $user = null): array
    {
        return [
            'comments' => $this->loadComments($project, null, $user),
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
        ];
    }
}
