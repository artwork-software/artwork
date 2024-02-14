<?php

namespace Artwork\Modules\Sage100\Services;

use App\Sage100\Sage100;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Sage100Service
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ColumnService $columnService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService,
        private readonly SageApiSettingsService $sageApiSettingsService
    ) {
    }

    public function importDataToBudget(int|null $count): int
    {
        $data = $this->getData($count);
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
                                SageNotAssignedData::where('sage_id', $item['ID'])
                                    ->firstOr(function () use ($project, $item): bool|int {
                                        $modelObject = $this->sageNotAssignedDataService->store($item);
                                        return $modelObject->update([
                                            'project_id' => $project->id
                                        ]);
                                    });
                                $addedData[] = $item;
                            }
                        } else {
                            if ($kto->sub_position_row_id === $foundedKST->first()->sub_position_row_id) {
                                $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();
                                $sageColumn->subPositionRows()->updateExistingPivot($kto->sub_position_row_id, [
                                    'value' => $item['Buchungsbetrag']
                                ]);
                            } else {
                                SageNotAssignedData::where('sage_id', $item['ID'])
                                    ->firstOr(function () use ($project, $item): bool|int {
                                        $modelObject = $this->sageNotAssignedDataService->store($item);
                                        return $modelObject->update([
                                            'project_id' => $project->id
                                        ]);
                                    });
                                $addedData[] = $item;
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
                            SageNotAssignedData::where('sage_id', $item['ID'])
                                ->firstOr(function () use ($project, $item): bool|int {
                                    $modelObject = $this->sageNotAssignedDataService->store($item);
                                    return $modelObject->update([
                                        'project_id' => $project->id
                                    ]);
                                });
                            $addedData[] = $item;
                        }
                    } else {
                        SageNotAssignedData::where('sage_id', $item['ID'])
                            ->firstOr(function () use ($project, $item): bool|int {
                                $modelObject = $this->sageNotAssignedDataService->store($item);
                                return $modelObject->update([
                                    'project_id' => $project->id
                                ]);
                            });
                        $addedData[] = $item;
                    }
                }



                $foundedProjects[] = $project->id;
                if (!in_array($item, $addedData)) {
                    SageNotAssignedData::where('sage_id', $item['ID'])
                        ->firstOr(function () use ($project, $item): bool|int {
                            $modelObject = $this->sageNotAssignedDataService->store($item);
                            return $modelObject->update([
                                'project_id' => $project->id
                            ]);
                        });
                    $addedData[] = $item;
                }
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

        if (!empty($data)) {
            $lastDataset = array_pop($data);
            if (!is_null($lastDataset) && isset($lastDataset['Buchungsdatum'])) {
                $this->sageApiSettingsService->updateBookingDate(Carbon::parse($lastDataset['Buchungsdatum']));
            }
        }
        return 0;
    }

    public function dropData($request): void
    {
        $table = Table::find($request->table_id);
        $columns = $table->columns()->whereNot('type', 'sage')->get();
        $subPosition = SubPosition::find($request->sub_position_id);
        $project = $table->project()->first();
        $check = $table->columns()->where('type', 'sage')->exists();
        $sageData = SageNotAssignedData::find($request->sage_data_id);
        $sageColumn = null;
        if (!$check) {
            $sageColumn = $this->columnService->createColumnInTable(
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
                $sageColumn->subPositionRows()->attach($subPositionRow, [
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


            $sageColumn->subPositionSumDetails()->createMany(
                $subPositions->map(fn($subPosition) => [
                    'sub_position_id' => $subPosition->id
                ])->all()
            );

            $mainPositions = MainPosition::where('table_id', $project->table()->first()->id)->get();

            $sageColumn->mainPositionSumDetails()->createMany(
                $mainPositions->map(fn($mainPosition) => [
                    'main_position_id' => $mainPosition->id
                ])->all()
            );

            $sageColumn->budgetSumDetails()->create([
                'type' => 'COST'
            ]);

            $sageColumn->budgetSumDetails()->create([
                'type' => 'EARNING'
            ]);
        }

        SubPositionRow::query()
            ->where('sub_position_id', $request->sub_position_id)
            ->where('position', '>', $request->positionBefore)
            ->increment('position');


        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $request->positionBefore + 1
        ]);

        $firstThreeColumns = $columns->shift(3);
        foreach ($firstThreeColumns as $column) {
            if ($column->name === 'KTO') {
                $subPositionRow->columns()->attach($column->id, [
                    'value' => $sageData->kto,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]);
            } elseif ($column->name === 'KST') {
                $subPositionRow->columns()->attach($column->id, [
                    'value' => $sageData->kst,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]);
            } elseif ($column->name === 'Beschreibung') {
                $subPositionRow->columns()->attach($column->id, [
                    'value' => $sageData->description,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]);
            }
        }

        // create columns for the rest of the columns without sage column
        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);


        if ($sageColumn === null) {
            $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();
        }

        $sageColumn->subPositionRows()->attach($subPositionRow->id, [
            'value' => $sageData->amount,
            'verified_value' => null,
            'linked_money_source_id' => null,
            'commented' => $subPositionRow->commented
        ]);

        $sageData->delete();
    }

    private function getData(int|null $count)
    {
        return app(Sage100::class)->getData($this->buildQuery($count));
    }

    /**
     * @return array<string, string>
     */
    private function buildQuery(int|null $count): array
    {
        $query = [];

        if ($count) {
            $query['startIndex'] = 0;
            $query['count'] = $count;
        }

        if ($desiredBookingDate = $this->sageApiSettingsService->getFirst()?->bookingDate) {
            $query['where'] = 'Buchungsdatum gt "' . Carbon::parse($desiredBookingDate)->format('d.m.Y') . '"';
            $query['orderBy'] = 'Buchungsdatum asc';
        }

        return $query;
    }
}
