<?php

namespace App\Support\Services;

use App\Models\MoneySource;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;

class MoneySourceCalculationService
{
    public function getPositionSumOfOneMoneySource(MoneySource $moneySource): int
    {
        $positionSum = 0;

        if ($moneySource->is_group) {
            $subMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
            foreach ($subMoneySources as $subMoneySource) {
                $positionSum += $this->getPositionSumOfOneMoneySource($subMoneySource);
            }
        }

        $positionSum += $this->calculateColumnCellLinkedSum($moneySource);
        $positionSum += $this->calculateBudgetSumDetailsLinkedSum($moneySource);
        $positionSum += $this->calculateSubPositionSumDetailsLinkedSum($moneySource);
        $positionSum += $this->calculateMainPositionDetailsLinkedSum($moneySource);

        return $positionSum;
    }

    private function calculateColumnCellLinkedSum(MoneySource $moneySource): int
    {
        $columnCells = ColumnCell::query()
            ->where('linked_money_source_id', $moneySource->id)
            ->latest('column_id')
            ->get()
            ->unique('sub_position_row_id');

        $columnCellsLinkedSum = 0;

        foreach ($columnCells as $columnCell) {
            if ($columnCell->linked_type === 'EARNING') {
                $columnCellsLinkedSum += floatval(str_replace(',', '.', $columnCell->value));
            } else {
                $columnCellsLinkedSum -= floatval(str_replace(',', '.', $columnCell->value));
            }
        }

        return $columnCellsLinkedSum;
    }

    private function calculateBudgetSumDetailsLinkedSum(MoneySource $moneySource): int
    {
        $budgetSumDetails = BudgetSumDetails::query()
            ->with('column.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $budgetSumDetailsLinkedSum = 0;

        foreach ($budgetSumDetails as $budgetSumDetail) {
            foreach ($budgetSumDetail->column->table->costSums as $costSum) {
                $budgetSumDetailsLinkedSum -= $costSum;
            }
            foreach ($budgetSumDetail->column->table->earningSums as $earningSum) {
                $budgetSumDetailsLinkedSum += $earningSum;
            }
        }

        return $budgetSumDetailsLinkedSum;
    }

    private function calculateSubPositionSumDetailsLinkedSum(MoneySource $moneySource): int
    {
        $subPositionSumDetails = SubPositionSumDetail::query()
            ->with('subPosition.mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $subPositionSumDetailsLinkedSum = 0;

        foreach ($subPositionSumDetails as $subPositionSumDetail) {
            foreach ($subPositionSumDetail->subPosition->columnSums as $columnSum) {
                if ($subPositionSumDetail->sumMoneySource->linked_type === 'EARNING') {
                    $subPositionSumDetailsLinkedSum += $columnSum['sum'];
                } else {
                    $subPositionSumDetailsLinkedSum -= $columnSum['sum'];
                }
            }
        }

        return $subPositionSumDetailsLinkedSum;
    }

    private function calculateMainPositionDetailsLinkedSum(MoneySource $moneySource): int
    {
        $mainPositionDetails = MainPositionDetails::query()
            ->with('mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $mainPositionDetailsLinkedSum = 0;

        foreach ($mainPositionDetails as $mainPositionDetail) {
            foreach ($mainPositionDetail->mainPosition->columnSums as $columnSum) {
                if ($mainPositionDetail->sumMoneySource->linked_type === 'EARNING') {
                    $mainPositionDetailsLinkedSum += $columnSum['sum'];
                } else {
                    $mainPositionDetailsLinkedSum -= $columnSum['sum'];
                }
            }
        }

        return $mainPositionDetailsLinkedSum;
    }
}
