<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
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

        // Wer alle Projekte bearbeiten/verwalten darf, muss sie auch öffnen können —
        // sonst sperrt ein fehlendes "view projects" User mit Schreibrechten aus.
        return $user->projects->contains($project->id) ||
            $project->users->contains($user->id) ||
            $isTeamMember ||
            $user->can(PermissionEnum::PROJECT_VIEW->value) ||
            $user->can(PermissionEnum::WRITE_PROJECTS->value) ||
            $user->can(PermissionEnum::PROJECT_MANAGEMENT->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);
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
        // Deckungsgleich zum Frontend ("Bearbeiten" zeigt sich bei 'write projects' bzw.
        // Schreibrecht im Projekt-Pivot) – sonst bekämen diese User nach dem Nachrüsten der
        // Autorisierung einen 403, wo sie vorher bearbeiten konnten.
        if ($user->can(PermissionEnum::WRITE_PROJECTS->value)) {
            return true;
        }

        if ($project->writeUsers->contains($user->id)) {
            return true;
        }

        foreach ($project->departments as $department) {
            if ($department->users->contains($user->id)) {
                return true;
            }
        }

        $isCreator = false;
        foreach ($project->events as $event) {
            if ($event->created_by?->id === $user->id) {
                $isCreator = true;
            }
        }

        return $user->can(PermissionEnum::PROJECT_MANAGEMENT->value) || $isCreator;
    }

    public function delete(User $user, Project $project): bool
    {
        $isCreator = false;
        foreach ($project->events as $event) {
            if ($event->created_by->id === $user->id) {
                $isCreator = true;
            }
        }
        return $user->can(PermissionEnum::PROJECT_DELETE->value) || $isCreator;
    }
}
