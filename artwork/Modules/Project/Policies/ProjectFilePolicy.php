<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\Project\Services\ProjectTabDocumentService;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectFilePolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ProjectComponentVisibilityService $projectComponentVisibilityService,
        private readonly ProjectTabDocumentService $projectTabDocumentService,
    ) {
    }

    public function view(User $user, ProjectFile $projectFile): bool
    {
        $project = $projectFile->project;

        // Check if user has access to the file
        $hasFileAccess = $projectFile->accessingUsers->contains($user->id);

        // Dateien gehören zu dem Tab, in dem sie hochgeladen wurden: ohne Sicht auf diesen Tab kein
        // Zugriff, außer die Datei wurde der Person ausdrücklich freigegeben (Admins via Gate::before).
        if (
            $projectFile->tab_id !== null &&
            !$hasFileAccess &&
            !$this->projectComponentVisibilityService->canSeeTab($user, $projectFile->tab_id)
        ) {
            return false;
        }

        // Budget-Dokumente nur für Freigegebene mit Sicht auf die Budget-Informationen und Admins – sonst
        // wäre die Freigabe-Auswahl über "Alle Dokumente" und den Download wirkungslos.
        if (!$this->projectTabDocumentService->canSeeBudgetDocument($user, $projectFile)) {
            return false;
        }

        // Basis ist die Projektsicht (ProjectPolicy::view: "view/write projects", Team, Abteilungen) –
        // dieselbe Regel wie für die Dokumentlisten (CanViewProject); ausdrückliche Freigabe genügt auch.
        return $hasFileAccess || ($project !== null && $user->can('view', $project));
    }

    public function create(User $user, Project $project, ?int $tabId = null): bool
    {
        // Hochladen in einen Tab setzt Sicht auf diesen Tab voraus
        if ($tabId !== null && !$this->projectComponentVisibilityService->canSeeTab($user, $tabId)) {
            return false;
        }

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
        return $this->view($user, $projectFile) && $this->canManage($user, $projectFile);
    }

    public function delete(User $user, ProjectFile $projectFile): bool
    {
        return $this->view($user, $projectFile) && $this->canManage($user, $projectFile);
    }

    public function forceDelete(User $user, ProjectFile $projectFile): bool
    {
        return $this->view($user, $projectFile) && $this->canManage($user, $projectFile);
    }

    /**
     * Ersetzen, Löschen und Freigabeliste ändern setzt Schreibrecht im Projekt voraus (ProjectPolicy::update –
     * dieselbe Basis wie die Löschen-Buttons der Dokumente-Komponenten). Leserecht oder eine Freigabe allein
     * reichen nicht. Budget-Dokumente pflegen zusätzlich die Personen mit Budget-Rolle (globale Budget-
     * Verwaltung, Budgetzugriff im Projekt): die Budget-Informationen bieten ihnen Bearbeiten/Löschen an.
     * Die Sicht auf das Budget-Dokument selbst (canSeeBudgetDocument) prüft view().
     */
    private function canManage(User $user, ProjectFile $projectFile): bool
    {
        $project = $projectFile->project;
        if ($project === null) {
            return false;
        }

        if ($user->can('update', $project)) {
            return true;
        }

        return $projectFile->is_budget_document && (
            $user->can(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value) ||
            $project->access_budget->contains('id', $user->id)
        );
    }
}
