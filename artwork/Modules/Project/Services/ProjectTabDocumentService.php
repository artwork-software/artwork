<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

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
     * Alle Dateien des Projekts ohne Tab oder aus Tabs, die die Person sehen darf.
     */
    public function loadVisibleDocuments(Project $project, ?User $user): Collection
    {
        if (!$user) {
            return $project->project_files;
        }

        $query = $project->project_files();
        $this->projectComponentVisibilityService->constrainToVisibleTabs($query, $user);

        return $query->get();
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
