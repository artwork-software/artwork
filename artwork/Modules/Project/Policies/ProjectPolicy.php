<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    // Globale Rechte, die den Zutritt zu jedem Projekt erlauben (write inklusive: wer alle
    // Projekte bearbeiten darf, muss sie auch öffnen können). "management projects" gehört
    // bewusst NICHT dazu — es erlaubt nur, im Projektteam als Projektleitung markiert zu werden.
    // Wird auch vom canEnter-Flag der Projektübersicht gelesen (ProjectController::mapProjectsToComponents).
    public const GLOBAL_ENTER_PERMISSIONS = [
        'view projects',
        'write projects',
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
        // "Schreibberechtigt im Projekt": globales Schreibrecht, sonst Team-Pivot (Schreibrecht /
        // Projektleitung), Ersteller:in oder zugewiesene Abteilung. Wird auch als Grundlage für
        // writeComponent() und das canWriteProject-Flag der Projektseite genutzt. Ein globales
        // Leserecht oder "Projektleitung sein" reicht bewusst nicht.
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

        foreach ($project->events as $event) {
            if ($event->created_by?->id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Schreiben in eine Tab-Komponente: Schreibrecht im Projekt (update) ist Grundvoraussetzung,
     * die Komponenten-Einstellung kann es nur weiter einschränken, nie erweitern. Globales
     * "write projects" übersteuert die Komponenten-Einstellung; Admins passieren via Gate::before.
     */
    public function writeComponent(User $user, Project $project, Component $component): bool
    {
        if ($user->can(PermissionEnum::WRITE_PROJECTS->value)) {
            return true;
        }

        return $this->update($user, $project) && $component->isEditableBy($user);
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
