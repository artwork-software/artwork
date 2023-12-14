<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        $isTeamMember = false;
        foreach ($project->departments as $department) {
            if ($department->users->contains($user->id)) {
                $isTeamMember = true;
            }
        }

        return $user->projects->contains($project->id) ||
            $project->users->contains($user->id) ||
            $isTeamMember ||
            $user->can(PermissionNameEnum::PROJECT_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value);
    }

    public function createProperties(User $user, Project $project): bool
    {
        $isTeamMember = false;
        foreach ($project->departments as $department) {
            if ($department->users->contains($user->id)) {
                $isTeamMember = true;
            }
        }
        $isCreator = false;
        foreach ($project->events as $event) {
            if ($event->user_id === $user->id) {
                $isCreator = true;
            }
        }

        return $user->can('create_and_edit_projects') ||
            $project->users->contains($user->id) ||
            $isTeamMember ||
            (bool)$user->projects()?->find($project->id)?->pivot?->is_manager === true ||
            $isCreator;
    }

    public function update(User $user, Project $project): bool
    {
        foreach ($project->departments as $department) {
            if ($department->users->contains($user->id)) {
                return true;
            }
        }

        $isCreator = false;
        foreach ($project->events as $event) {
            if ($event->created_by->id === $user->id) {
                $isCreator = true;
            }
        }

        return $user->can(PermissionNameEnum::PROJECT_UPDATE->value) || $isCreator;
    }

    public function delete(User $user, Project $project): bool
    {
        $isCreator = false;
        foreach ($project->events as $event) {
            if ($event->created_by->id === $user->id) {
                $isCreator = true;
            }
        }
        return $user->can(PermissionNameEnum::PROJECT_DELETE->value) || $isCreator;
    }

    public function restore(User $user, Project $project): void
    {
    }

    public function forceDelete(User $user, Project $project): void
    {
    }
}
