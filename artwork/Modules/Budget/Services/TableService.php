<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Repositories\TableRepository;
use Artwork\Modules\Project\Models\Project;

readonly class TableService
{
    public function __construct(private TableRepository $tableRepository)
    {
    }

    public function createTableInProject(
        Project $project,
        string $name,
        bool $isTemplate
    ): Table|Model {
        $table = new Table();
        $table->project_id = $project->id;
        $table->name = $name;
        $table->is_template = $isTemplate;
        return $this->tableRepository->save($table);
    }

    public function forceDelete(
        Table $table,
        MainPositionService $mainPositionService,
        ColumnService $columnService
    ): void {
        $table->mainPositions->each(function (MainPosition $mainPosition) use ($mainPositionService): void {
            $mainPositionService->forceDelete($mainPosition);
        });

        $table->columns->each(function (Column $column) use ($columnService): void {
            $columnService->forceDelete($column);
        });

        $this->tableRepository->forceDelete($table);
    }
}
