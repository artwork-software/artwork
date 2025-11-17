<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;

class ProjectTabBudgetService
{
    public function __construct(
        private readonly BudgetService $budgetService,
    ) {
    }

    public function buildBudgetPayload(Project $project): array
    {
        $loadedProjectInformation = [];
        $loadedProjectInformation = $this->budgetService->getBudgetForProjectTab(
            $project,
            $loadedProjectInformation
        );

        $users = $project->users->map(
            fn (User $user) => [
                'id'                  => $user->id,
                'first_name'          => $user->first_name,
                'last_name'           => $user->last_name,
                'profile_photo_url'   => $user->profile_photo_url,
                'email'               => $user->email,
                'departments'         => $user->departments,
                'position'            => $user->position,
                'business'            => $user->business,
                'phone_number'        => $user->phone_number,
                'project_management'  => $user->can(PermissionEnum::PROJECT_MANAGEMENT->value),
                'pivot_access_budget' => (bool) ($user->pivot?->access_budget),
                'pivot_is_manager'    => (bool) ($user->pivot?->is_manager),
                'pivot_can_write'     => (bool) ($user->pivot?->can_write),
                'pivot_delete_permission' => (bool) ($user->pivot?->delete_permission),
            ]
        );

        return [
            'BudgetTab' => $loadedProjectInformation['BudgetTab'] ?? [],
            'users' => $users->values()->toArray(),
        ];
    }
}

