<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Repositories\ColumnRepository;

readonly class ColumnService
{
    public function __construct(private ColumnRepository $columnRepository)
    {
    }

    public function createColumnInTable(
        Table $table,
        string $name,
        string $subName,
        string $type,
        int|null $linked_first_column = null,
        int|null $linked_second_column = null
    ): Column|Model {
        $column = new Column();
        $column->table_id = $table->id;
        $column->name = $name;
        $column->linked_first_column = $linked_first_column;
        $column->linked_second_column = $linked_second_column;
        $column->subName = $subName;
        $column->type = $type;
        return $this->columnRepository->save($column);
    }

    public function setColumnSubName(int $table_id): void
    {
        $table = Table::find($table_id);
        $columns = $table->columns()->get();

        $count = 1;

        foreach ($columns as $column) {
            if (empty($column->subName)) {
                continue;
            }
            $column->update([
                'subName' => $this->getNameFromNumber($count)
            ]);
            $count++;
        }
    }

    public function getNameFromNumber(int $num): string
    {
        $numeric = ($num - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2) . $letter;
        } else {
            return $letter;
        }
    }

    public function forceDelete(
        Column $column,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionSumDetailService $subPositionSumDetailService,
        BudgetSumDetailsService $budgetSumDetailsService,
        ColumnCellService $columnCellService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): void {
        $column->subPositionSumDetails->each(
            function (SubPositionSumDetail $subPositionSumDetail) use (
                $sumCommentService,
                $sumMoneySourceService,
                $subPositionSumDetailService
            ): void {
                $subPositionSumDetailService->forceDelete(
                    $subPositionSumDetail,
                    $sumCommentService,
                    $sumMoneySourceService
                );
            }
        );

        $column->mainPositionSumDetails->each(
            function (MainPositionDetails $mainPositionDetails) use (
                $sumCommentService,
                $sumMoneySourceService,
                $mainPositionDetailsService
            ): void {
                $mainPositionDetailsService->forceDelete(
                    $mainPositionDetails,
                    $sumCommentService,
                    $sumMoneySourceService
                );
            }
        );

        $column->budgetSumDetails->each(
            function (BudgetSumDetails $budgetSumDetails) use (
                $budgetSumDetailsService,
                $sumCommentService,
                $sumMoneySourceService
            ): void {
                $budgetSumDetailsService->forceDelete(
                    $budgetSumDetails,
                    $sumCommentService,
                    $sumMoneySourceService
                );
            }
        );

        $column->cells->each(
            function (ColumnCell $columnCell) use (
                $columnCellService,
                $cellCommentService,
                $cellCalculationService,
                $sageNotAssignedDataService,
                $sageAssignedDataService
            ): void {
                $columnCellService->forceDelete(
                    $columnCell,
                    $cellCommentService,
                    $cellCalculationService,
                    $sageNotAssignedDataService,
                    $sageAssignedDataService
                );
            }
        );

        $this->columnRepository->forceDelete($column);
    }
}
