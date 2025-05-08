<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Contracts\DataAggregator;
use Artwork\Migrating\ImportConfig;
use Artwork\Migrating\Models\ProjectGroupImportModel;
use Artwork\Migrating\Models\ProjectImportModel;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\MainPositionService;
use Artwork\Modules\Budget\Services\TableService;
use Artwork\Modules\BudgetColumnSetting\Services\BudgetColumnSettingService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
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
        BudgetService $budgetService,
        TableService $tableService,
        ColumnService $columnService,
        MainPositionService $mainPositionService,
        BudgetColumnSettingService $columnSettingService,
        SageApiSettingsService $sageApiSettingsService
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
