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
        private readonly ProjectDocumentPlacementService $placementService,
    ) {
    }

    /**
     * Dateien eines Upload-Bereichs samt Bereichs-Einstellungen und der Bereiche, die beim Hochladen bzw. danach
     * als Sichtbarkeit wählbar sind (nur Bereiche in Tabs, die die Person sieht).
     */
    public function buildDocumentPayload(
        Project $project,
        ?ComponentInTab $componentInTab = null,
        ?User $user = null,
        bool $inFolder = false,
    ): array {
        $scope = $componentInTab?->scope ?? [];
        if ($user !== null) {
            // Tabs, die die Person nicht sehen darf, fallen aus der Tab-Auswahl der Komponente heraus
            $scope = $this->projectComponentVisibilityService->restrictScopeToVisibleTabs($user, $scope);
        }

        $placementKey = $componentInTab?->id !== null
            ? ProjectDocumentPlacementService::keyFor((int) $componentInTab->id, $inFolder)
            : null;
        $documents = $this->loadDocuments($project, $scope, $placementKey);

        return [
            'documents' => $documents,
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'placement' => $placementKey !== null ? [
                'key' => $placementKey,
                'settings' => $this->placementService->find($placementKey)['settings']
                    ?? $this->placementService->normalizeSettings(null),
                'uploaded_count' => $project->project_files()->where('uploaded_via', $placementKey)->count(),
            ] : null,
            'placements' => $this->selectablePlacements($user),
            'globalUploadLimits' => $this->placementService->globalLimits(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, scope: list<int>}>
     */
    private function selectablePlacements(?User $user): array
    {
        $visibleTabIds = $user !== null && !$this->isAdmin($user)
            ? $this->projectComponentVisibilityService->visibleTabIds($user)->map(fn ($id): int => (int) $id)->all()
            : null;

        return $this->placementService->placements()
            ->filter(fn (array $placement): bool => $visibleTabIds === null
                || array_intersect($placement['tab_ids'], $visibleTabIds) !== [])
            ->map(fn (array $placement): array => [
                'key' => $placement['key'],
                'label' => $placement['label'],
                'scope' => $placement['scope'],
            ])
            ->values()
            ->all();
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
        $this->constrainToVisibleDocumentTabs($query, $user);
        $this->constrainToVisibleBudgetDocuments($query, $project, $user);

        return $query->get()->each(fn (ProjectFile $file) => $this->appendVisibleKeys($file));
    }

    /**
     * Wie constrainToVisibleTabs(), zusätzlich Dateien, die ausdrücklich in einem Upload-Bereich eines sichtbaren
     * Tabs angezeigt werden (visible_in).
     */
    public function constrainToVisibleDocumentTabs(Builder|Relation $query, User $user): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        $visibleTabIds = $this->projectComponentVisibilityService->visibleTabIds($user)
            ->map(fn ($id): int => (int) $id)
            ->all();
        $visibleKeys = $this->placementService->placements()
            ->filter(fn (array $placement): bool => array_intersect($placement['tab_ids'], $visibleTabIds) !== [])
            ->pluck('key')
            ->all();

        $query->where(function (Builder $query) use ($visibleTabIds, $visibleKeys): void {
            $query->whereIn('tab_id', $visibleTabIds)->orWhereNull('tab_id');
            foreach ($visibleKeys as $key) {
                $query->orWhereJsonContains('visible_in', $key);
            }
        });
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

    private function loadDocuments(Project $project, array $scope, ?string $placementKey = null): Collection
    {
        if (empty($scope) && $placementKey === null) {
            return new Collection();
        }

        $query = $project->project_files();
        if ($placementKey !== null) {
            $this->placementService->constrainToPlacement($query, $placementKey, array_map('intval', $scope));
        } else {
            $query->whereIn('tab_id', $scope);
        }

        return $query
            // Herkunftsvermerk für Uploads externer Personen (freigegebene Tabs)
            ->with(['externalAccess:id,email,name,crm_contact_id', 'externalAccess.crmContact:id,display_name'])
            ->get()
            ->each(fn (ProjectFile $file) => $this->appendVisibleKeys($file));
    }

    /**
     * Bereiche, in denen die Datei erscheint – für das Sichtbarkeits-Badge und die Auswahl "Sichtbar in".
     */
    private function appendVisibleKeys(ProjectFile $file): void
    {
        $file->setAttribute('visible_keys', $this->placementService->visibleKeysOf($file));
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
