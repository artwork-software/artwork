<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Department\Http\Resources\DepartmentIndexResource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\User\Models\User;

class ProjectTabTeamService
{
    public function buildTeamPayload(Project $project): array
    {
        $users = $project->users()->get()->map(
            fn(User $user) => [
                'id'                      => $user->id,
                'first_name'              => $user->first_name,
                'last_name'               => $user->last_name,
                'profile_photo_url'       => $user->profile_photo_url,
                'email'                   => $user->email,
                'departments'             => $user->departments,
                'description'             => $user->description,
                'position'                => $user->position,
                'pronouns'                => $user->pronouns,
                'email_private'           => (bool)$user->email_private,
                'phone_private'           => (bool)$user->phone_private,
                'phone_number'            => $user->phone_number,
                'project_management'      => $user->can(PermissionEnum::PROJECT_MANAGEMENT->value),
                'pivot_access_budget'     => (bool)($user->pivot?->access_budget),
                'pivot_is_manager'        => (bool)($user->pivot?->is_manager),
                'pivot_can_write'         => (bool)($user->pivot?->can_write),
                'pivot_delete_permission' => (bool)($user->pivot?->delete_permission),
                'pivot_roles'             => (array)($user->pivot?->roles),
            ]
        )->values();

        return [
            'project' => [
                'id'                      => $project->id,
                'usersArray'              => $users,
                'project_managers'        => $project->managerUsers,
                'write_auth'              => $project->writeUsers,
                'delete_permission_users' => $project->delete_permission_users,
                'departments'             => DepartmentIndexResource::collection($project->departments)->resolve(),
                'projectRoles'            => ProjectRole::all(),
            ],
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'projectWriteIds'   => $project->writeUsers()->pluck('user_id'),
        ];
    }
}


