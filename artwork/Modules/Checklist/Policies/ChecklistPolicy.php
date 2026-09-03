<?php

namespace Artwork\Modules\Checklist\Policies;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Eigene Listen darf jede Person sehen, bearbeiten und löschen. "To-dos verwalten"
 * (can edit checklist) und "Checklisten-Vorlagen verwalten" gelten für fremde Listen.
 * Projekt-/Abteilungs-Zugehörigkeit und Task-Zuweisung geben zusätzlich Sicht bzw. Schreibrecht.
 */
class ChecklistPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Checklist $checklist): bool
    {
        return $user->canAny([
                PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value,
                PermissionEnum::CHECKLIST_EDIT_PERMISSION->value,
            ])
            || $checklist->user_id === $user->id
            || $this->isSharedWith($user, $checklist)
            || $this->isAssignedToAnyTask($user, $checklist)
            || $this->canAccessProject($user, $checklist, 'view');
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, Checklist $checklist): bool
    {
        return $user->canAny([
                PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value,
                PermissionEnum::CHECKLIST_EDIT_PERMISSION->value,
            ])
            || $checklist->user_id === $user->id
            || $this->isSharedWith($user, $checklist)
            || $this->isAssignedToAnyTask($user, $checklist)
            || $this->canAccessProject($user, $checklist, 'update');
    }

    public function delete(User $user, Checklist $checklist): bool
    {
        return $user->canAny([
                PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value,
                PermissionEnum::CHECKLIST_EDIT_PERMISSION->value,
            ])
            || $checklist->user_id === $user->id
            || $this->canAccessProject($user, $checklist, 'update');
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
