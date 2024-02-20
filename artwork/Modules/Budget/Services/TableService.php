<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Repositories\TableRepository;
use Artwork\Modules\Project\Models\Project;

class TableService
{
    /**
     * @param TableRepository $tableRepository
     */
    public function __construct(
        private readonly TableRepository $tableRepository,
        private readonly MainPositionService $mainPositionService,
        private readonly ColumnService $columnService
    ) {
    }


    /**
     * function createTableByProject
     * @param Project $project
     * @param string $name
     * @param bool $isTemplate
     * @return Table|Model
     */
    public function createTableInProject(Project $project, string $name, bool $isTemplate): Table|Model
    {
        $table = new Table();
        $table->project_id = $project->id;
        $table->name = $name;
        $table->is_template = $isTemplate;
        return $this->tableRepository->save($table);
    }

    public function delete(Table $table): void
    {
        $table->mainPositions->each(function (MainPosition $mainPosition): void {
            $this->mainPositionService->delete($mainPosition);
        });

        $table->columns->each(function (Column $column): void {
            $this->columnService->delete($column);
        });

        $this->tableRepository->delete($table);
    }
}
