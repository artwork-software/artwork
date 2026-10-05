<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Contracts\DataAggregator;
use Artwork\Migrating\ImportConfig;
use Artwork\Migrating\Models\ProjectImportModel;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ImportProject
{
    use Queueable;
    use InteractsWithQueue;

    public function __construct(
        private readonly ImportConfig $config,
        private readonly DataAggregator $dataAggregator,
        private readonly ProjectImportModel $projectImportModel
    ) {
    }

    public function handle(
        Dispatcher $dispatcher,
        ProjectService $projectService,
        BudgetService $budgetService
    ): void {
        if (!$project = $projectService->getNonProjectGroupByName($this->projectImportModel->name)) {
            logger()->debug('Project not found, creating new project');
            $project = $this->createProject(
                $projectService,
                $this->projectImportModel->name,
                $this->projectImportModel->description,
                false
            );
            $budgetService->generateBasicBudgetValues(
                $project
            );
        }

        if ($this->projectImportModel->projectGroupIdentifier) {
            $projectGroupImportModel = $this->dataAggregator->findProjectGroup(
                $this->projectImportModel->projectGroupIdentifier
            );
            if (
                $projectGroupImportModel &&
                $projectGroup = $projectService->getProjectGroupByName(
                    $projectGroupImportModel->name
                )
            ) {
                if ($project->groups()->where('group_id', $projectGroup->id)->doesntExist()) {
                    $projectService->associateProjectWithGroup($project, $projectGroup);
                }
            }
        }

        foreach ($this->dataAggregator->findEvents($this->projectImportModel->identifier) as $event) {
            $dispatcher->dispatch(
                new ImportEvent(
                    $this->config,
                    $this->dataAggregator,
                    $event,
                    $project
                )
            );
        }
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
