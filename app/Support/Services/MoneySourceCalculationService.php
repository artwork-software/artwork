<?php

namespace App\Support\Services;

use App\Models\BudgetSumDetails;
use App\Models\ColumnCell;
use App\Models\MainPosition;
use App\Models\MainPositionDetails;
use App\Models\MoneySource;
use App\Models\Project;
use App\Models\SubPosition;
use App\Models\SubPositionRow;
use App\Models\SubpositionSumDetail;
use App\Models\Table;

class MoneySourceCalculationService
{
    public function calculatePositionSumPerMoneySource($moneySource): int
    {
        $positionSum = 0;
        $positionSum += $this->getPositionSumOfOneMoneySource($moneySource);

        return $positionSum;
    }

    public function getPositionSumOfOneMoneySource($moneySource)
    {
        $positionSum = 0;
        $subMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        $columnCells = ColumnCell::where('linked_money_source_id', $moneySource->id)
            ->latest('column_id')
            ->get()
            ->unique('sub_position_row_id');

        $subPositionSumDetails = SubpositionSumDetail::with('subPosition.mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $mainPositionSumDetails = MainPositionDetails::with('mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $budgetSumDetails = BudgetSumDetails::with('column.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        if ($moneySource->is_group) {
            foreach ($subMoneySources as $subMoneySource) {
                $positionSum += $this->getPositionSumofOneMoneySource($subMoneySource);
            }
        }
        foreach ($columnCells as $columnCell) {
            if ($columnCell->linked_type === 'EARNING') {
                $positionSum += $columnCell->value;
            } else {
                $positionSum -= $columnCell->value;
            }
        }

        foreach ($budgetSumDetails as $detail) {
            foreach ($detail->column->table->costSums as $costSum) {
                $positionSum -= $costSum;
            }
            foreach ($detail->column->table->earningSums as $earningSum) {
                $positionSum += $earningSum;
            }
        }

        foreach ($subPositionSumDetails as $detail) {
            foreach ($detail->subPosition->columnSums as $columnSum) {
                if ($detail->sumMoneySource->linked_type === 'EARNING') {
                    $positionSum += $columnSum['sum'];
                } else {
                    $positionSum -= $columnSum['sum'];
                }
            }
        }

        foreach ($mainPositionSumDetails as $detail) {
            foreach ($detail->mainPosition->columnSums as $columnSum) {
                if ($detail->sumMoneySource->linked_type === 'EARNING') {
                    $positionSum += $columnSum['sum'];
                } else {
                    $positionSum -= $columnSum['sum'];
                }
            }
        }
        return $positionSum;
    }
}
