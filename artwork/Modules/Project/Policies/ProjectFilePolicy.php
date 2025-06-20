<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectFilePolicy
{
    use HandlesAuthorization;

    public function view(User $user, ProjectFile $projectFile): bool
    {
        $project = $projectFile->project;

        // Check if user is a team member
        $isTeamMember = false;
        foreach ($project->departments as $department) {
            if ($department->users->contains($user->id)) {
                $isTeamMember = true;
                break;
            }
        }

        // Check if user has access to the file
        $hasFileAccess = $projectFile->accessingUsers->contains($user->id);

        // Check if user is attached to the project
        $isAttachedToProject = $project->users()->where('user_id', $user->id)->exists();

        return $isAttachedToProject ||
            $user->projects->contains($project->id) ||
            $project->users->contains($user->id) ||
            $isTeamMember ||
            $hasFileAccess ||
            $user->can(PermissionEnum::PROJECT_VIEW->value);
    }

    public function create(User $user, Project $project): bool
    {
        // Check if user is a team member
        $isTeamMember = false;
        foreach ($project->departments as $department) {
            if ($department->users->contains($user->id)) {
                $isTeamMember = true;
                break;
            }
        }

        // Check if user is attached to the project
        $isAttachedToProject = $project->users()->where('user_id', $user->id)->exists();

        return $isAttachedToProject ||
            $user->projects->contains($project->id) ||
            $project->users->contains($user->id) ||
            $isTeamMember ||
            $user->can(PermissionEnum::PROJECT_VIEW->value);
    }

    public function update(User $user, ProjectFile $projectFile): bool
    {
        return $this->view($user, $projectFile);
    }

    public function delete(User $user, ProjectFile $projectFile): bool
    {
        return $this->view($user, $projectFile);
    }

    public function forceDelete(User $user, ProjectFile $projectFile): bool
    {
        return $this->view($user, $projectFile);
    }
}
