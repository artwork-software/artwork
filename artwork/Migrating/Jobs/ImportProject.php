<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Contracts\DataAggregator;
use Artwork\Migrating\ImportConfig;
use Artwork\Migrating\Models\ProjectImportModel;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ImportProject
{
    use Queueable;
    use InteractsWithQueue;

    private Dispatcher $dispatcher;

    private RoomService $roomService;
    private ProjectService $projectService;

    public function __construct(
        private readonly ImportConfig       $config,
        private readonly DataAggregator     $dataAggregator,
        private readonly ProjectImportModel $project
    )
    {
        $this->dispatcher = app()->get(Dispatcher::class);
        $this->projectService = app()->get(ProjectService::class);
    }

    public function handle(): void
    {
        if (!$project = $this->projectService->getByName($this->project->name)->first()) {
            logger()->debug('Project not found, creating new project');
            $project = new Project();
            $project->name = $this->project->name;
            $project->shift_description = $this->project->description;
            $this->projectService->save($project);
        }

        foreach ($this->dataAggregator->findEvents($this->project->identifier) as $event) {
            $this->dispatcher->dispatch(new ImportEvent($this->config, $this->dataAggregator, $event, $project));
        }
    }
}
