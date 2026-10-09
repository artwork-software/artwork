<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ProjectComponentVisibilityService $projectComponentVisibilityService,
    ) {
    }

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

        return $user->can(PermissionEnum::ADD_EDIT_OWN_PROJECT->value) ||
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

        // Kein Zweig "Ersteller:in eines Termins im Projekt": die Projektzuordnung im Termin-Dialog
        // ist bewusst ohne Projektrecht möglich – sonst bekäme jede Person mit Terminrecht so
        // Schreibzugriff auf beliebige Projekte. (Der frühere Zweig las events.created_by, das es
        // nicht gibt, und war damit nie aktiv.)
        return false;
    }

    /**
     * Sehen einer Tab-Komponente (die App-API baut die Tab-Payload serverseitig); bei "Sehen dürfen nur
     * die Folgenden" nur die Eingetragenen und Admins, wie im Web.
     */
    public function viewComponent(User $user, Project $project, Component $component): bool
    {
        return $this->view($user, $project)
            && $this->projectComponentVisibilityService->canSeeComponent($user, $component);
    }

    /**
     * Schreiben in eine Tab-Komponente: Schreibrecht im Projekt (update) ist Grundvoraussetzung,
     * die Komponenten-Einstellung kann es nur weiter einschränken, nie erweitern. Globales
     * "write projects" übersteuert die Bearbeiten-Einstellung, aber nicht die Sicht-Einschränkung
     * ("Sehen dürfen nur die Folgenden"): was die Person nicht sehen darf, darf sie auch nicht
     * schreiben. Admins passieren via Gate::before.
     */
    public function writeComponent(User $user, Project $project, Component $component): bool
    {
        if ($user->can(PermissionEnum::WRITE_PROJECTS->value)) {
            return $this->projectComponentVisibilityService->canSeeComponent($user, $component);
        }

        return $this->update($user, $project) && $component->isEditableBy($user);
    }

    /**
     * writeComponent für Inhalte eines Komponenten-Typs, die nicht als Komponentenwert gespeichert werden.
     * Ohne Komponenten-Datensatz greift die Projekt-Bearbeitungsregel allein.
     */
    public function writeComponentType(User $user, Project $project, ProjectTabComponentEnum $type): bool
    {
        $component = Component::query()->where('type', $type->value)->first();

        return $component !== null
            ? $this->writeComponent($user, $project, $component)
            : $this->update($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->can(PermissionEnum::PROJECT_DELETE->value)) {
            return true;
        }

        // Projektteam-Häkchen "Löschen" (project_user.delete_permission) — wurde bisher nur im
        // Frontend gelesen, die Policy antwortete 403.
        if ($project->delete_permission_users()->where('users.id', $user->id)->exists()) {
            return true;
        }

        // Bewusst kein Termin-Ersteller-Zweig (siehe update()).
        return false;
    }
}
