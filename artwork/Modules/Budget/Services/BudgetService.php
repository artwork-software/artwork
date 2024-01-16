<?php

namespace Artwork\Modules\Budget\Services;

use App\Enums\BudgetTypesEnum;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function __construct(
        private readonly TableService        $budgetTableService,
        private readonly ColumnService       $budgetColumnService,
        private readonly MainPositionService $mainPositionService,
    )
    {
    }


    public function generateBasicBudgetValues(Project $project): void
    {
        DB::transaction(function() use ($project) {
            $table = $this->budgetTableService->createTableInProject($project, $project->name . ' Budgettabelle', false);

            $columns = new Collection();

            $columns[] = $this->budgetColumnService->createColumnInTable(table: $table, name: 'KTO', subName: '', type: 'empty');
            $columns[] = $this->budgetColumnService->createColumnInTable(table: $table, name: 'A', subName: '', type: 'empty');
            $columns[] = $this->budgetColumnService->createColumnInTable(table: $table, name: 'Position', subName: '', type: 'empty');
            $columns[] = $this->budgetColumnService->createColumnInTable(table: $table, name: date('Y') . ' €', subName: 'A', type: 'empty');


            $costMainPosition = $this->mainPositionService->createMainPosition(
                table: $table,
                budgetTypesEnum: BudgetTypesEnum::BUDGET_TYPE_COST,
                name: 'Hauptpostion',
                position: $table->mainPositions()->where('type', BudgetTypesEnum::BUDGET_TYPE_COST)->max('position') + 1
            );

            $earningMainPosition = $this->mainPositionService->createMainPosition(
                table: $table,
                budgetTypesEnum: BudgetTypesEnum::BUDGET_TYPE_EARNING,
                name: 'Hauptpostion',
                position: $table->mainPositions()->where('type', BudgetTypesEnum::BUDGET_TYPE_EARNING)->max('position') + 1
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

            $firstThreeColumns = $columns->shift(3);

            $costSubPositionRow->columns()->attach($firstThreeColumns->pluck('id'), [
                'value' => "",
                'verified_value' => "",
                'linked_money_source_id' => null,
            ]);

            $earningSubPositionRow->columns()->attach($firstThreeColumns->pluck('id'), [
                'value' => "",
                'verified_value' => "",
                'linked_money_source_id' => null,
            ]);

            $costSubPositionRow->columns()->attach($columns->pluck('id'), [
                'value' => 0,
                'verified_value' => null,
                'linked_money_source_id' => null,
            ]);

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

            $earningSubPositionRow->columns()->attach($columns->pluck('id'), [
                'value' => 0,
                'verified_value' => null,
                'linked_money_source_id' => null,
            ]);
        });
    }
}
