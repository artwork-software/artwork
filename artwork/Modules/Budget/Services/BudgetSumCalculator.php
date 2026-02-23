<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\Table;

class BudgetSumCalculator
{
    public function computeAndAttachSums(Table $table): void
    {
        if (!$table->relationLoaded('mainPositions')) {
            return;
        }

        $mainPositionIds = [];
        $subPositionIds = [];
        $columnIds = $table->relationLoaded('columns')
            ? $table->columns->pluck('id')->toArray()
            : [];

        foreach ($table->mainPositions as $mainPosition) {
            $mainPositionIds[] = $mainPosition->id;
            if ($mainPosition->relationLoaded('subPositions')) {
                foreach ($mainPosition->subPositions as $subPosition) {
                    $subPositionIds[] = $subPosition->id;
                }
            }
        }

        $allMainPositionDetails = MainPositionDetails::whereIn('main_position_id', $mainPositionIds)
            ->withCount('comments', 'sumMoneySource')
            ->get()
            ->groupBy('main_position_id');

        $allSubPositionSumDetails = SubPositionSumDetail::whereIn('sub_position_id', $subPositionIds)
            ->withCount('comments', 'sumMoneySource')
            ->get()
            ->groupBy('sub_position_id');

        $allBudgetSumDetails = BudgetSumDetails::whereIn('column_id', $columnIds)
            ->withCount('comments', 'sumMoneySource')
            ->get()
            ->groupBy(fn ($detail) => $detail->type . ':' . $detail->column_id);

        $costSums = [];
        $earningSums = [];
        $commentedCostSums = [];
        $commentedEarningSums = [];

        foreach ($table->mainPositions as $mainPosition) {
            $this->computeMainPositionSums(
                $mainPosition,
                $allMainPositionDetails,
                $allSubPositionSumDetails
            );

            $isCost = $mainPosition->type === 'BUDGET_TYPE_COST';

            if ($mainPosition->relationLoaded('subPositions')) {
                foreach ($mainPosition->subPositions as $subPosition) {
                    if (!$subPosition->relationLoaded('subPositionRows')) {
                        continue;
                    }

                    foreach ($subPosition->subPositionRows as $row) {
                        if (!$row->relationLoaded('cells')) {
                            continue;
                        }

                        foreach ($row->cells as $cell) {
                            $columnId = $cell->column_id;
                            $rawValue = str_replace(',', '.', $cell->value ?: '0');

                            $column = $table->relationLoaded('columns')
                                ? $table->columns->firstWhere('id', $columnId)
                                : null;

                            $isColumnCommented = $column && $column->commented;
                            $isCellCommented = $cell->commented;
                            $isRowCommented = $row->commented;

                            if ($isColumnCommented || $isCellCommented || $isRowCommented) {
                                $target = $isCost ? 'cost' : 'earning';
                                if ($target === 'cost') {
                                    $commentedCostSums[$columnId] = ($commentedCostSums[$columnId] ?? 0)
                                        + floatval($rawValue);
                                } else {
                                    $commentedEarningSums[$columnId] = ($commentedEarningSums[$columnId] ?? 0)
                                        + floatval($rawValue);
                                }
                            } else {
                                if ($isCost) {
                                    $costSums[$columnId] = ($costSums[$columnId] ?? 0) + floatval($rawValue);
                                } else {
                                    $earningSums[$columnId] = ($earningSums[$columnId] ?? 0) + floatval($rawValue);
                                }
                            }

                            $this->computeCellValues($cell);
                        }
                    }
                }
            }
        }

        $sortedColumns = $table->relationLoaded('columns')
            ? $table->columns->sortBy('position')->values()
            : collect();
        $skipColumnIds = $sortedColumns->take(3)->pluck('id')->toArray();

        $costSums = collect($costSums)->filter(
            fn ($val, $key) => !in_array($key, $skipColumnIds)
        );
        $earningSums = collect($earningSums)->filter(
            fn ($val, $key) => !in_array($key, $skipColumnIds)
        );

        $costSumDetails = $this->buildBudgetSumDetailsMap($allBudgetSumDetails, 'COST');
        $earningSumDetails = $this->buildBudgetSumDetailsMap($allBudgetSumDetails, 'EARNING');

        $table->setAttribute('costSums', $costSums);
        $table->setAttribute('earningSums', $earningSums);
        $table->setAttribute('commentedCostSums', collect($commentedCostSums));
        $table->setAttribute('commentedEarningSums', collect($commentedEarningSums));
        $table->setAttribute('costSumDetails', $costSumDetails);
        $table->setAttribute('earningSumDetails', $earningSumDetails);
    }

    private function computeMainPositionSums(
        MainPosition $mainPosition,
        $allMainPositionDetails,
        $allSubPositionSumDetails
    ): void {
        $mainPositionSumDetails = $allMainPositionDetails->get($mainPosition->id, collect())
            ->keyBy('column_id');

        $columnSums = [];
        $hasVerifiedChanges = false;

        if ($mainPosition->relationLoaded('subPositions')) {
            foreach ($mainPosition->subPositions as $subPosition) {
                $this->computeSubPositionSums($subPosition, $allSubPositionSumDetails);

                $subColumnSums = $subPosition->getAttribute('columnSums');
                if ($subColumnSums) {
                    foreach ($subColumnSums as $columnId => $data) {
                        if (!isset($columnSums[$columnId])) {
                            $columnSums[$columnId] = ['sum' => '0', 'hasComments' => false, 'hasMoneySource' => false];
                        }
                        $columnSums[$columnId]['sum'] = bcadd(
                            $columnSums[$columnId]['sum'],
                            $data['sum'] ?? '0',
                            2
                        );
                    }
                }

                if (!$hasVerifiedChanges && $subPosition->relationLoaded('subPositionRows')) {
                    foreach ($subPosition->subPositionRows as $row) {
                        if ($row->relationLoaded('cells')) {
                            foreach ($row->cells as $cell) {
                                if ($cell->verified_value !== '' && $cell->verified_value !== $cell->value) {
                                    $hasVerifiedChanges = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($columnSums as $columnId => &$data) {
            $detail = $mainPositionSumDetails->get($columnId);
            $data['hasComments'] = $detail && $detail->comments_count > 0;
            $data['hasMoneySource'] = $detail && $detail->sum_money_source_count > 0;
        }

        $mainPosition->setAttribute('columnSums', collect($columnSums));
        $mainPosition->setAttribute('columnVerifiedChanges', $hasVerifiedChanges);
    }

    private function computeSubPositionSums(
        SubPosition $subPosition,
        $allSubPositionSumDetails
    ): void {
        $sumDetails = $allSubPositionSumDetails->get($subPosition->id, collect())
            ->keyBy('column_id');

        $columnSums = [];

        if ($subPosition->relationLoaded('subPositionRows')) {
            $sortedCells = collect();

            foreach ($subPosition->subPositionRows as $row) {
                if ($row->commented) {
                    continue;
                }
                if (!$row->relationLoaded('cells')) {
                    continue;
                }

                foreach ($row->cells as $cell) {
                    if ($cell->commented) {
                        continue;
                    }

                    $column = $cell->relationLoaded('column') ? $cell->column : null;
                    if ($column && $column->commented) {
                        continue;
                    }

                    $sortedCells->push($cell);
                }
            }

            $grouped = $sortedCells->groupBy('column_id');

            $sortedGrouped = $grouped->sortKeys();
            $skipped = $sortedGrouped->skip(3);

            foreach ($skipped as $columnId => $cells) {
                $sum = $cells->reduce(function ($carry, $cell) {
                    $decimalValue = str_replace(',', '.', $cell->value ?: '0');
                    if (!is_numeric($decimalValue)) {
                        $decimalValue = '0';
                    }
                    return bcadd($carry ?: '0', $decimalValue, 2);
                }, '0');

                $detail = $sumDetails->get($columnId);

                $columnSums[$columnId] = [
                    'sum' => $sum,
                    'hasComments' => $detail && $detail->comments_count > 0,
                    'hasMoneySource' => $detail && $detail->sum_money_source_count > 0,
                ];
            }
        }

        $subPosition->setAttribute('columnSums', collect($columnSums));
    }

    private function computeCellValues($cell): void
    {
        if ($cell->relationLoaded('sageAssignedData') && $cell->sageAssignedData->isNotEmpty()) {
            $cell->setAttribute('sage_value', (string) $cell->sageAssignedData->sum('buchungsbetrag'));
        } else {
            $cell->setAttribute('sage_value', null);
        }

        $cell->setAttribute('current_value', $cell->value);
    }

    private function buildBudgetSumDetailsMap($allBudgetSumDetails, string $type): \Illuminate\Support\Collection
    {
        return collect($allBudgetSumDetails)
            ->filter(fn ($details, $key) => str_starts_with($key, $type . ':'))
            ->mapWithKeys(function ($details, $key) {
                $columnId = (int) explode(':', $key)[1];
                $detail = $details->first();
                return [
                    $columnId => [
                        'hasComments' => $detail && $detail->comments_count > 0,
                        'hasMoneySource' => $detail && $detail->sum_money_source_count > 0,
                    ]
                ];
            });
    }
}
