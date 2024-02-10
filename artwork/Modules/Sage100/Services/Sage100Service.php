<?php

namespace Artwork\Modules\Sage100\Services;

use App\Sage100\Sage100;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Database\Eloquent\Builder;

class Sage100Service
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly BudgetService $budgetService,
        private readonly ColumnService $columnService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService
    ) {
    }


    public function getData(int $count)
    {
        return app(Sage100::class)->getData([
            "startIndex" => 0,
            "count" => $count,
        ]);
    }

    public function importDataToBudget(): void
    {
        $data = $this->getData(100);
        //dd($data);
        $foundedProjects = [];
        $addedData = [];
        foreach ($data as $item) {
            $projects = $this->projectService->getProjectsByCostCenter($item['KstTraeger']);
            foreach ($projects as $project) {
                if (!in_array($project->id, $foundedProjects)) {
                    // check if project has a sage column in first table and create if not
                    $check = $project->table()->first()->columns()->where('type', 'sage')->exists();
                    if (!$check) {
                        // create column
                        $column = $this->columnService->createColumnInTable(
                            table:      $project->table()->first(),
                            name:       'Sage Abgleich',
                            subName:    '-',
                            type:       'sage'
                        );

                        $this->columnService->setColumnSubName(
                            table_id:   $project->table()->first()->id
                        );
                        $subPositionRows = SubPositionRow::whereHas(
                            'subPosition.mainPosition',
                            function (Builder $query) use ($project): void {
                                $query->where('table_id', $project->table()->first()->id);
                            }
                        )->pluck('id');



                        foreach ($subPositionRows as $subPositionRow) {
                            $column->subPositionRows()->attach($subPositionRow, [
                                'value' => 0,
                                'verified_value' => null,
                                'linked_money_source_id' => null,
                                'commented' => SubPositionRow::find($subPositionRow)->commented
                            ]);
                        }

                        $subPositions = SubPosition::whereHas(
                            'mainPosition',
                            function (Builder $query) use ($project): void {
                                $query->where('table_id', $project->table()->first()->id);
                            }
                        )->get();


                        $column->subPositionSumDetails()->createMany(
                            $subPositions->map(fn($subPosition) => [
                                'sub_position_id' => $subPosition->id
                            ])->all()
                        );

                        $mainPositions = MainPosition::where('table_id', $project->table()->first()->id)->get();

                        $column->mainPositionSumDetails()->createMany(
                            $mainPositions->map(fn($mainPosition) => [
                                'main_position_id' => $mainPosition->id
                            ])->all()
                        );

                        $column->budgetSumDetails()->create([
                            'type' => 'COST'
                        ]);

                        $column->budgetSumDetails()->create([
                            'type' => 'EARNING'
                        ]);
                    }
                }

                $foundedKTO = ColumnCell::where('value', $item['SaKto'])->get();

                if ($foundedKTO->count() > 1) {
                    foreach ($foundedKTO as $kto) {
                        $foundedKST = ColumnCell::where('value', $item['KstStelle'])
                            ->where('sub_position_row_id', $kto->sub_position_row_id)->get();
                        if ($foundedKST->count() > 1) {
                            foreach ($foundedKST as $kst) {
                                if ($kto->sub_position_row_id === $kst->sub_position_row_id) {
                                    $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();
                                    $sageColumn->subPositionRows()->updateExistingPivot($kto->sub_position_row_id, [
                                        'value' => $item['Buchungsbetrag']
                                    ]);
                                }
                            }
                        } else {
                            if ($kto->sub_position_row_id === $foundedKST->first()->sub_position_row_id) {
                                $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();
                                $sageColumn->subPositionRows()->updateExistingPivot($kto->sub_position_row_id, [
                                    'value' => $item['Buchungsbetrag']
                                ]);
                            }
                        }
                    }
                } else {
                    $singleKTO = $foundedKTO->first();
                    $foundedKST = ColumnCell::where('value', $item['KstStelle'])->first();
                    $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();
                    //dd($singleKTO?->sub_position_row_id, $foundedKST?->sub_position_row_id);
                    if ($singleKTO && $foundedKST) {
                        if ($singleKTO?->sub_position_row_id === $foundedKST?->sub_position_row_id) {
                            $sageColumn->subPositionRows()->updateExistingPivot($singleKTO->sub_position_row_id, [
                                'value' => $item['Buchungsbetrag']
                            ]);
                            //dd('hier');
                        } else {
                            $sageColumn->subPositionRows()->attach($singleKTO->sub_position_row_id, [
                                'value' => $item['Buchungsbetrag'],
                                'verified_value' => null,
                                'linked_money_source_id' => null,
                                'commented' => SubPositionRow::find($singleKTO->sub_position_row_id)->commented
                            ]);
                        }
                    }
                }



                $foundedProjects[] = $project->id;
                $modelObject = $this->sageNotAssignedDataService->store($item);
                $modelObject->update([
                    'project_id' => $project->id
                ]);
                // add data to addedData array to check if data was added
                $addedData[] = $item;
            }

            // check if item is in addedData array and if not add it
            if (!in_array($item, $addedData)) {
                // check if item is in sage_not_assigned_data table and if not add it
                SageNotAssignedData::where('sage_id', $item['ID'])
                    ->firstOr(function () use ($item): SageNotAssignedData {
                        return $this->sageNotAssignedDataService->store($item);
                    });
            }
        }
    }
}
