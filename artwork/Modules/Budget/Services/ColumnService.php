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

class ColumnService
{

    public function __construct(
        private readonly ColumnRepository $columnRepository,
        private readonly ColumnCellService $columnCellService,
        private readonly SubPositionSumDetailService $subPositionSumDetailService,
        private readonly MainPositionDetailsService $mainPositionDetailsService,
        private readonly BudgetSumDetailsService $budgetSumDetailsService
    ) {
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

    public function delete(Column $column): void
    {
        $column->subPositionSumDetails->each(function (SubPositionSumDetail $subPositionSumDetail): void {
            $this->subPositionSumDetailService->delete($subPositionSumDetail);
        });

        $column->mainPositionSumDetails->each(function (MainPositionDetails $mainPositionDetails): void {
            $this->mainPositionDetailsService->delete($mainPositionDetails);
        });

        $column->budgetSumDetails->each(function (BudgetSumDetails $budgetSumDetails): void {
            $this->budgetSumDetailsService->delete($budgetSumDetails);
        });

        $column->cells->each(function (ColumnCell $columnCell): void {
            $this->columnCellService->delete($columnCell);
        });

        $this->columnRepository->delete($column);
    }
}
