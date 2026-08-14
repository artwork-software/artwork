<?php

namespace Artwork\Modules\MoneySource\Services;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Illuminate\Database\Eloquent\Collection;

class MoneySourceCalculationService
{
    /**
     * Pro Zeile zählt genau eine verknüpfte Zelle: bevorzugt die der
     * budgetrelevanten Spalte, sonst die der hintersten Wertspalte
     * (Position-Reihenfolge, analog ColumnRelevanceService). Vorher wurde
     * die höchste column_id genommen — nach Duplizieren/Wiederherstellen
     * oder manuellem Umhängen des Flags traf das die falsche Spalte.
     */
    public function getLinkedColumnCellsPerRow(array|int $moneySourceIds, array $with = []): Collection
    {
        return ColumnCell::query()
            ->whereIn('linked_money_source_id', (array) $moneySourceIds)
            ->with(array_merge(['column'], $with))
            ->get()
            ->sortByDesc(fn(ColumnCell $columnCell) => (
                ($columnCell->column?->relevant_for_project_groups ? 1_000_000 : 0)
                + ($columnCell->column?->position ?? 0)
            ))
            ->unique('sub_position_row_id');
    }

    public function getPositionSumOfOneMoneySource(MoneySource $moneySource): float
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

    private function calculateColumnCellLinkedSum(MoneySource $moneySource): float
    {
        $columnCells = $this->getLinkedColumnCellsPerRow($moneySource->id);

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

    private function calculateBudgetSumDetailsLinkedSum(MoneySource $moneySource): float
    {
        $budgetSumDetails = BudgetSumDetails::query()
            ->with('column.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $budgetSumDetailsLinkedSum = 0;

        foreach ($budgetSumDetails as $budgetSumDetail) {
            // Nur die verknüpfte Spalte zählt, und das Vorzeichen richtet sich nach linked_type
            // (siehe Referenz-Logik in MoneySourceController). Vorher wurden ALLE Spalten der
            // Tabelle summiert und linked_type ignoriert -> falsche Restbudgets/Schwellenwarnungen.
            $linkedType = $budgetSumDetail->sumMoneySource->linked_type ?? null;

            foreach ($budgetSumDetail->column->table->costSums as $columnId => $costSum) {
                if ($columnId !== $budgetSumDetail->column_id || $budgetSumDetail->type !== 'COST') {
                    continue;
                }
                $budgetSumDetailsLinkedSum += $linkedType === 'EARNING' ? $costSum : -$costSum;
            }

            foreach ($budgetSumDetail->column->table->earningSums as $columnId => $earningSum) {
                if ($columnId !== $budgetSumDetail->column_id || $budgetSumDetail->type !== 'EARNING') {
                    continue;
                }
                $budgetSumDetailsLinkedSum += $linkedType === 'EARNING' ? $earningSum : -$earningSum;
            }
        }

        return $budgetSumDetailsLinkedSum;
    }

    private function calculateSubPositionSumDetailsLinkedSum(MoneySource $moneySource): float
    {
        $subPositionSumDetails = SubPositionSumDetail::query()
            ->with('subPosition.mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $subPositionSumDetailsLinkedSum = 0;

        foreach ($subPositionSumDetails as $subPositionSumDetail) {
            // Nur die verknüpfte Spalte zählt (siehe Referenz-Logik in MoneySourceController),
            // sonst geht der Betrag pro Wertspalte mehrfach ein.
            foreach ($subPositionSumDetail->subPosition->columnSums as $columnId => $columnSum) {
                if ($columnId !== $subPositionSumDetail->column_id) {
                    continue;
                }
                if ($subPositionSumDetail->sumMoneySource->linked_type === 'EARNING') {
                    $subPositionSumDetailsLinkedSum += $columnSum['sum'];
                } else {
                    $subPositionSumDetailsLinkedSum -= $columnSum['sum'];
                }
            }
        }

        return $subPositionSumDetailsLinkedSum;
    }

    private function calculateMainPositionDetailsLinkedSum(MoneySource $moneySource): float
    {
        $mainPositionDetails = MainPositionDetails::query()
            ->with('mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $mainPositionDetailsLinkedSum = 0;

        foreach ($mainPositionDetails as $mainPositionDetail) {
            // Nur die verknüpfte Spalte zählt (siehe Referenz-Logik in MoneySourceController),
            // sonst geht der Betrag pro Wertspalte mehrfach ein.
            foreach ($mainPositionDetail->mainPosition->columnSums as $columnId => $columnSum) {
                if ($columnId !== $mainPositionDetail->column_id) {
                    continue;
                }
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
