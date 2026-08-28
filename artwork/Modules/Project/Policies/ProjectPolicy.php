<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    // Globale Rechte, die den Zutritt zu jedem Projekt erlauben (write/management inklusive:
    // wer alle Projekte bearbeiten/verwalten darf, muss sie auch öffnen können). Wird auch
    // vom canEnter-Flag der Projektübersicht gelesen (ProjectController::mapProjectsToComponents).
    public const GLOBAL_ENTER_PERMISSIONS = [
        'view projects',
        'write projects',
        'management projects',
    ];

    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        // Läuft als Middleware (CanViewProject) auf jedem Projekt-Request: erst die
        // in-memory gecachten Permission-Checks, erst danach Team-Queries.
        foreach (self::GLOBAL_ENTER_PERMISSIONS as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        if (
            $project->relationLoaded('users')
                ? $project->users->contains($user->id)
                : $project->users()->whereKey($user->id)->exists()
        ) {
            return true;
        }

        return $project->departments()
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->exists();
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

        // Projektleitung (is_manager-Pivot, wird mit can_write=false angelegt) und
        // Projektersteller:in (projects.user_id): das Frontend bietet beiden das
        // Bearbeiten an (InfoTab projectManagerIds, "Edit basic data"-Menü).
        if ($project->managerUsers->contains($user->id) || $project->user_id === $user->id) {
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
