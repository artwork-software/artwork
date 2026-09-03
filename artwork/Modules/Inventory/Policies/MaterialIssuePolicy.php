<?php

namespace Artwork\Modules\Inventory\Policies;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Gemeinsame Policy für interne und externe Materialausgaben.
 *
 * Verwalten darf, wer "Inventardisposition" hat. Zusätzlich dürfen Projektmitglieder mit
 * Schreibrecht die Ausgaben IHRES Projekts anlegen und pflegen (Projekt-Tab "Materialausgaben"),
 * ohne das hausweite Dispositionsrecht zu brauchen. Admins passieren via Gate::before.
 */
class MaterialIssuePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::INVENTORY_DISPOSITION->value);
    }

    /**
     * Lesen (Detail, Druckansicht): wer verwalten darf – zusätzlich alle mit Lesezugriff auf das
     * Projekt der Ausgabe (Projekt-Tab "Materialausgaben" zeigt die Druckansicht auch Lesenden).
     */
    public function view(User $user, InternalIssue|ExternalIssue $issue): bool
    {
        if ($this->manage($user, $issue)) {
            return true;
        }

        if ($issue->project_id === null) {
            return false;
        }

        $project = Project::query()->find($issue->project_id);

        return $project !== null && $user->can('view', $project);
    }

    /**
     * Anlegen: hausweites Recht ODER Schreibrecht im Projekt, dem die Ausgabe zugeordnet wird.
     */
    public function create(User $user, ?int $projectId = null): bool
    {
        if ($user->can(PermissionEnum::INVENTORY_DISPOSITION->value)) {
            return true;
        }

        return $projectId !== null && $this->canWriteProject($user, $projectId);
    }

    public function update(User $user, InternalIssue|ExternalIssue $issue): bool
    {
        return $this->manage($user, $issue);
    }

    public function delete(User $user, InternalIssue|ExternalIssue $issue): bool
    {
        return $this->manage($user, $issue);
    }

    private function manage(User $user, InternalIssue|ExternalIssue $issue): bool
    {
        if ($user->can(PermissionEnum::INVENTORY_DISPOSITION->value)) {
            return true;
        }

        if ($issue instanceof ExternalIssue && $issue->issued_by_id === $user->id) {
            return true;
        }

        return $issue->project_id !== null && $this->canWriteProject($user, (int) $issue->project_id);
    }

    private function canWriteProject(User $user, int $projectId): bool
    {
        $project = Project::query()->find($projectId);

        return $project !== null && $user->can('update', $project);
    }
}
