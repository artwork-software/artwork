<?php

namespace Artwork\Modules\Checklist\Policies;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Eigene Listen darf jede Person sehen, bearbeiten und löschen. "To-dos verwalten"
 * (can edit checklist) und "Checklisten-Vorlagen verwalten" gelten für fremde Listen.
 * Projekt-/Abteilungs-Zugehörigkeit und Task-Zuweisung geben zusätzlich Sicht bzw. Schreibrecht.
 * Sicht über das Projekt setzt bei Listen aus einem Tab zusätzlich Sicht auf diesen Tab voraus.
 */
class ChecklistPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ProjectComponentVisibilityService $projectComponentVisibilityService,
    ) {
    }

    public function view(User $user, Checklist $checklist): bool
    {
        if ($this->hasPersonalAccess($user, $checklist) || $this->isAssignedToAnyTask($user, $checklist)) {
            return true;
        }

        if ($this->isInTabHiddenFrom($user, $checklist)) {
            return false;
        }

        return $this->isSharedWith($user, $checklist)
            || $this->canAccessProject($user, $checklist, 'view');
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, Checklist $checklist): bool
    {
        if ($this->hasPersonalAccess($user, $checklist) || $this->isAssignedToAnyTask($user, $checklist)) {
            return true;
        }

        if ($this->isInTabHiddenFrom($user, $checklist)) {
            return false;
        }

        return $this->isSharedWith($user, $checklist)
            || $this->canAccessProject($user, $checklist, 'update');
    }

    public function delete(User $user, Checklist $checklist): bool
    {
        if (
            $user->canAny([
                PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value,
                PermissionEnum::CHECKLIST_EDIT_PERMISSION->value,
            ])
            || $checklist->user_id === $user->id
        ) {
            return true;
        }

        if ($this->isInTabHiddenFrom($user, $checklist)) {
            return false;
        }

        return $this->canAccessProject($user, $checklist, 'update');
    }

    /**
     * Zugriff unabhängig von Projekt und Tab: globale Checklisten-Rechte, Ersteller:in und Personen,
     * mit denen die Liste direkt geteilt ist.
     */
    private function hasPersonalAccess(User $user, Checklist $checklist): bool
    {
        return $user->canAny([
                PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value,
                PermissionEnum::CHECKLIST_EDIT_PERMISSION->value,
            ])
            || $checklist->user_id === $user->id
            || $checklist->users->contains($user->id);
    }

    /**
     * Zugriff über das Projekt setzt bei Listen eines Tabs Sicht auf diesen Tab voraus – wie
     * "Alle Checklisten" (Admins via Gate::before, auch Listen gelöschter eingeschränkter Tabs).
     */
    private function isInTabHiddenFrom(User $user, Checklist $checklist): bool
    {
        return $checklist->tab_id !== null &&
            !$this->projectComponentVisibilityService->canSeeTab($user, (int) $checklist->tab_id);
    }

    /**
     * Projekt-Checklisten folgen dem Projektrecht (ProjectPolicy::view/update): globales Schreibrecht,
     * Team-Schreibrecht, Projektleitung, Ersteller:in und Abteilung – dieselben Quellen wie writeComponent().
     */
    private function canAccessProject(User $user, Checklist $checklist, string $ability): bool
    {
        return $checklist->project !== null && $user->can($ability, $checklist->project);
    }

    private function isSharedWith(User $user, Checklist $checklist): bool
    {
        if ($checklist->users->contains($user->id)) {
            return true;
        }

        return (bool) $checklist->project?->users->contains($user->id);
    }

    private function isAssignedToAnyTask(User $user, Checklist $checklist): bool
    {
        // Vorher: $checklist->tasks->each(...) — each() gibt immer die Collection zurück (truthy),
        // wodurch update() für jede eingeloggte Person true war.
        return $checklist->tasks->contains(
            fn ($task): bool => $task->task_users->contains($user->id)
        );
    }
}
