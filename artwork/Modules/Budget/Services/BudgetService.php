<?php

namespace Artwork\Modules\Budget\Services;

use App\Enums\BudgetTypesEnum;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\BudgetColumnSetting\Services\BudgetColumnSettingService;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class BudgetService
{
    public function __construct(
        private TableService $budgetTableService,
        private ColumnService $budgetColumnService,
        private MainPositionService $mainPositionService,
        private BudgetColumnSettingService $budgetColumnSettingService
    ) {
    }

    public function generateBasicBudgetValues(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            $table = $this->budgetTableService->createTableInProject(
                $project,
                $project->name . ' Budgettabelle',
                false
            );

            $columns = new Collection();

            $columns[] = $this->budgetColumnService->createColumnInTable(
                table: $table,
                name: $this->budgetColumnSettingService->getColumnNameByColumnPosition(0),
                subName: '',
                type: 'empty'
            );
            $columns[] = $this->budgetColumnService->createColumnInTable(
                table: $table,
                name: $this->budgetColumnSettingService->getColumnNameByColumnPosition(1),
                subName: '',
                type: 'empty'
            );
            $columns[] = $this->budgetColumnService->createColumnInTable(
                table: $table,
                name: $this->budgetColumnSettingService->getColumnNameByColumnPosition(2),
                subName: '',
                type: 'empty'
            );
            $columns[] = $this->budgetColumnService->createColumnInTable(
                table: $table,
                name: date('Y') . ' €',
                subName: 'A',
                type: 'empty'
            );

            $costMainPosition = $this->mainPositionService->createMainPosition(
                table: $table,
                budgetTypesEnum: BudgetTypesEnum::BUDGET_TYPE_COST,
                name: 'Hauptpostion',
                position: $table->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_COST)
                    ->max('position') + 1
            );

            $earningMainPosition = $this->mainPositionService->createMainPosition(
                table: $table,
                budgetTypesEnum: BudgetTypesEnum::BUDGET_TYPE_EARNING,
                name: 'Hauptpostion',
                position: $table->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_EARNING)
                    ->max('position') + 1
            );

            $costSubPosition = $costMainPosition->subPositions()->create([
                'name' => 'Unterposition',
                'position' => $costMainPosition->subPositions()->max('position') + 1
            ]);

            $earningSubPosition = $earningMainPosition->subPositions()->create([
                'name' => 'Unterposition',
                'position' => $earningMainPosition->subPositions()->max('position') + 1
            ]);

            $costSubPositionRow = $costSubPosition->subPositionRows()->create([
                'commented' => false,
                'position' => $costSubPosition->subPositionRows()->max('position') + 1
            ]);

            $earningSubPositionRow = $earningSubPosition->subPositionRows()->create([
                'commented' => false,
                'position' => $earningSubPosition->subPositionRows()->max('position') + 1

            ]);

            foreach ($columns->shift(3) as $firstThreeColumn) {
                $costSubPositionRow->cells()->create([
                    'column_id' => $firstThreeColumn->id,
                    'sub_position_row_id' => $costSubPositionRow->id,
                    'value' => 0,
                    'verified_value' => "",
                    'linked_money_source_id' => null,
                ]);
                $earningSubPositionRow->cells()->create([
                    'column_id' => $firstThreeColumn->id,
                    'sub_position_row_id' => $earningSubPositionRow->id,
                    'value' => 0,
                    'verified_value' => "",
                    'linked_money_source_id' => null,
                ]);
            }

            foreach ($columns as $column) {
                $costSubPositionRow->cells()->create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $costSubPositionRow->id,
                    'value' => 0,
                    'verified_value' => "",
                    'linked_money_source_id' => null,
                ]);
                $earningSubPositionRow->cells()->create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $earningSubPositionRow->id,
                    'value' => 0,
                    'verified_value' => "",
                    'linked_money_source_id' => null,
                ]);
            }

            $costMainPosition->mainPositionSumDetails()->create([
                'column_id' => $columns->first()->id
            ]);

            $earningMainPosition->mainPositionSumDetails()->create([
                'column_id' => $columns->first()->id
            ]);

            $costSubPosition->subPositionSumDetails()->create([
                'column_id' => $columns->first()->id
            ]);

            $earningSubPosition->subPositionSumDetails()->create([
                'column_id' => $columns->first()->id
            ]);

            BudgetSumDetails::create([
                'column_id' => $columns->first()->id,
                'type' => 'COST'
            ]);

            BudgetSumDetails::create([
                'column_id' => $columns->first()->id,
                'type' => 'EARNING'
            ]);
        });
    }
}
