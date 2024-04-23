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
        ColumnService $columnService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService
    ): void {
        $table->mainPositions->each(
            function (MainPosition $mainPosition) use (
                $mainPositionService,
                $sumCommentService,
                $sumMoneySourceService,
                $subPositionVerifiedService,
                $subPositionSumDetailService,
                $subPositionRowService,
                $rowCommentService,
                $columnCellService,
                $mainPositionVerifiedService,
                $mainPositionDetailsService,
                $subPositionService
            ): void {
                $mainPositionService->forceDelete(
                    $mainPosition,
                    $sumCommentService,
                    $sumMoneySourceService,
                    $subPositionVerifiedService,
                    $subPositionSumDetailService,
                    $subPositionRowService,
                    $rowCommentService,
                    $columnCellService,
                    $mainPositionVerifiedService,
                    $mainPositionDetailsService,
                    $subPositionService
                );
            }
        );

        $table->columns->each(
            function (Column $column) use ($columnService, $sumCommentService, $sumMoneySourceService): void {
                $columnService->forceDelete($column, $sumCommentService, $sumMoneySourceService);
            }
        );

        $this->tableRepository->forceDelete($table);
    }
}
