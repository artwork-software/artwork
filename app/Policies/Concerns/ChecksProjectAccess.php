<?php

namespace App\Policies\Concerns;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Policies\ProjectPolicy;
use Artwork\Modules\User\Models\User;

/**
 * Künstler*innen und Unterkünfte hängen an keinem Projekt: Lesen darf, wer irgendein Projekt öffnen
 * darf, Verwalten, wer in irgendeinem Projekt schreibberechtigt ist (Spiegel von ProjectPolicy).
 */
trait ChecksProjectAccess
{
    protected function hasAnyProjectAccess(User $user): bool
    {
        foreach (ProjectPolicy::GLOBAL_ENTER_PERMISSIONS as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        if ($user->projects()->exists()) {
            return true;
        }

        return $user->departments()->whereHas('projects')->exists();
    }

    protected function hasAnyProjectWriteAccess(User $user): bool
    {
        if ($user->can(PermissionEnum::WRITE_PROJECTS->value)) {
            return true;
        }

        $isTeamWriter = $user->projects()
            ->where(function ($query): void {
                $query->where('project_user.can_write', true)
                    ->orWhere('project_user.is_manager', true);
            })
            ->exists();

        if ($isTeamWriter) {
            return true;
        }

        if (Project::query()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $user->departments()->whereHas('projects')->exists();
    }
}
