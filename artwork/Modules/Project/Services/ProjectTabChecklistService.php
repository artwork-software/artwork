<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Checklist\Http\Resources\ChecklistIndexResource;
use Artwork\Modules\Checklist\Http\Resources\ChecklistTemplateIndexResource;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;

class ProjectTabChecklistService
{
    public function buildChecklistPayload(Project $project, ?ComponentInTab $componentInTab = null): array
    {
        $userId = Auth::id();
        $scope = $componentInTab?->scope ?? [];

        $openedChecklists = User::where('id', $userId)
            ->first()
            ?->opened_checklists ?? [];

        $checklistTemplates = ChecklistTemplateIndexResource::collection(
            ChecklistTemplate::all()
        )->resolve();

        $publicChecklists = $this->getFilteredChecklists($project, $scope, false, $userId);
        $privateChecklists = $this->getFilteredChecklists($project, $scope, true, $userId);

        return [
            'opened_checklists' => $openedChecklists,
            'checklist_templates' => $checklistTemplates,
            'public_checklists' => $publicChecklists,
            'private_checklists' => $privateChecklists,
        ];
    }

    public function buildAllChecklistsPayload(Project $project): array
    {
        $userId = Auth::id();

        $openedChecklists = User::where('id', $userId)
            ->first()
            ?->opened_checklists ?? [];

        $publicAllChecklists = $this->getFilteredChecklists($project, [], false, $userId);
        $privateAllChecklists = $this->getFilteredChecklists($project, [], true, $userId);

        return [
            'opened_checklists' => $openedChecklists,
            'public_all_checklists' => $publicAllChecklists,
            'private_all_checklists' => $privateAllChecklists,
        ];
    }

    private function getFilteredChecklists(Project $project, array $scope, bool $private, int $userId): array
    {
        $query = $project->checklists()
            ->with(['users', 'tasks.task_users'])
            ->where('private', $private);

        if (!empty($scope)) {
            $query->whereIn('tab_id', $scope);
        }

        // Public checklists are visible to everyone who can view the component
        if (!$private) {
            return ChecklistIndexResource::collection($query->get())->resolve();
        }

        // Private checklists require user to be assigned, creator, or in project team
        if (!$project->relationLoaded('users')) {
            $project->load('users');
        }

        $checklists = $query->get()->filter(function ($checklist) use ($userId, $project) {
            $isInChecklistUsers = $checklist->users->contains('id', $userId);
            $isInTaskUsers = $checklist->tasks->contains(function ($task) use ($userId) {
                return $task->task_users->contains('id', $userId);
            });
            $isCreator = $checklist->user_id === $userId;
            $isInProjectTeam = $project->users->contains('id', $userId);

            return $isInChecklistUsers || $isInTaskUsers || $isCreator || $isInProjectTeam;
        });

        return ChecklistIndexResource::collection($checklists)->resolve();
    }
}

