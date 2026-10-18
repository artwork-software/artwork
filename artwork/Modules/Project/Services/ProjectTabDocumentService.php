<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProjectTabDocumentService
{
    public function __construct(
        private readonly ProjectComponentVisibilityService $projectComponentVisibilityService,
    ) {
    }

    public function buildDocumentPayload(
        Project $project,
        ?ComponentInTab $componentInTab = null,
        ?User $user = null
    ): array {
        $scope = $componentInTab?->scope ?? [];
        if ($user !== null) {
            // Tabs, die die Person nicht sehen darf, fallen aus der Tab-Auswahl der Komponente heraus
            $scope = $this->projectComponentVisibilityService->restrictScopeToVisibleTabs($user, $scope);
        }

        $documents = $this->loadDocuments($project, $scope);

        return [
            'documents' => $documents,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
        ];
    }

    public function buildAllDocumentsPayload(Project $project, ?User $user = null): array
    {
        $documents = $this->loadVisibleDocuments($project, $user);
        $hiddenTabNames = $this->getHiddenTabNames($project, $user);

        return [
            'documents' => $documents,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'hiddenTabNames' => $hiddenTabNames,
        ];
    }

    /**
     * Alle Dateien des Projekts ohne Tab oder aus Tabs, die die Person sehen darf. Budget-Dokumente nur,
     * wenn die Person sie auch in den Budget-Informationen sähe (siehe canSeeBudgetDocument()).
     */
    public function loadVisibleDocuments(Project $project, ?User $user): Collection
    {
        if (!$user) {
            return $project->project_files;
        }

        $query = $project->project_files();
        $this->projectComponentVisibilityService->constrainToVisibleTabs($query, $user);
        $this->constrainToVisibleBudgetDocuments($query, $project, $user);

        return $query->get();
    }

    /**
     * Schränkt eine Dateiabfrage des Projekts auf die Budget-Dokumente ein, die die Person sehen darf
     * (Abfrage-Gegenstück zu canSeeBudgetDocument()); andere Dateien bleiben unberührt. Auch für die
     * Dokumentlisten der App-API (AppSystemComponentService).
     */
    public function constrainToVisibleBudgetDocuments(Builder|Relation $query, Project $project, User $user): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        $canSeeBudgetSection = $this->canSeeBudgetDocumentsSection($project, $user);
        $query->where(function (Builder $query) use ($user, $canSeeBudgetSection): void {
            $query->where('is_budget_document', false);
            if ($canSeeBudgetSection) {
                $query->orWhereHas('accessingUsers', fn (Builder $query) => $query->whereKey($user->id));
            }
        });
    }

    /**
     * Spiegel von ProjectTabBudgetInformationService::visibleProjectFiles(): Budget-Dokumente sehen Admins
     * sowie freigegebene Personen, die den Dokumente-Bereich der Budget-Informationen sehen (globale
     * Budget-Verwaltung, Budgetzugriff im Projekt oder Projektleitung). Andere Dateien sind nicht betroffen.
     */
    public function canSeeBudgetDocument(User $user, ProjectFile $projectFile): bool
    {
        if (!$projectFile->is_budget_document || $this->isAdmin($user)) {
            return true;
        }

        return $projectFile->project !== null &&
            $projectFile->accessingUsers->contains('id', $user->id) &&
            $this->canSeeBudgetDocumentsSection($projectFile->project, $user);
    }

    private function canSeeBudgetDocumentsSection(Project $project, User $user): bool
    {
        return $user->can(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value) ||
            $project->access_budget->contains('id', $user->id) ||
            $project->managerUsers->contains('id', $user->id);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }

    private function loadDocuments(Project $project, array $scope): Collection
    {
        if (empty($scope)) {
            return new Collection();
        }

        return $project->project_files()
            ->whereIn('tab_id', $scope)
            // Herkunftsvermerk für Uploads externer Personen (freigegebene Tabs)
            ->with(['externalAccess:id,email,name,crm_contact_id', 'externalAccess.crmContact:id,display_name'])
            ->get();
    }

    /**
     * @return string[]
     */
    private function getHiddenTabNames(Project $project, ?User $user): array
    {
        if (!$user) {
            return [];
        }

        $visibleTabIds = $this->projectComponentVisibilityService->visibleTabIds($user);

        $hiddenTabIdsWithDocuments = $project->project_files()
            ->whereNotNull('tab_id')
            ->whereNotIn('tab_id', $visibleTabIds)
            ->distinct()
            ->pluck('tab_id');

        if ($hiddenTabIdsWithDocuments->isEmpty()) {
            return [];
        }

        return ProjectTab::query()
            ->whereIn('id', $hiddenTabIdsWithDocuments)
            ->pluck('name')
            ->toArray();
    }
}
