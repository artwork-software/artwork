<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Contracts\DataAggregator;
use Artwork\Migrating\ImportConfig;
use Artwork\Migrating\Models\ProjectGroupImportModel;
use Artwork\Migrating\Models\ProjectImportModel;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ImportProjectGroups
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(
        private readonly ImportConfig $config,
        private readonly DataAggregator $dataAggregator,
        private readonly ProjectGroupImportModel $projectGroupImportModel
    ) {
    }

    public function handle(
        ProjectService $projectService,
        BudgetService $budgetService
    ): void {
        if ($projectService->getProjectGroupByName($this->projectGroupImportModel->name)) {
            return;
        }
        $projectGroup = $this->createProject(
            $projectService,
            $this->projectGroupImportModel->name,
            $this->projectGroupImportModel->description,
            true
        );

        $budgetService->generateBasicBudgetValues(
            $projectGroup
        );
    }

    private function createProject(
        ProjectService $projectService,
        string $name,
        string $description,
        bool $isGroup
    ): Project {
        $project = new Project();
        $project->name = $name;
        $project->shift_description = $description;
        $project->is_group = $isGroup;
        return $projectService->save($project);
    }
}
