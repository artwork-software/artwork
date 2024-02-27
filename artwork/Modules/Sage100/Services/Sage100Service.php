<?php

namespace Artwork\Modules\Sage100\Services;

use App\Sage100\Sage100;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\SageAssignedDataService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Project\Models\Project;
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
        private readonly SageApiSettingsService $sageApiSettingsService,
        private readonly SageAssignedDataService $sageAssignedDataService
    ) {
    }

    public function importDataToBudget(int|null $count): int
    {
        //import php timeout 10 minutes
        ini_set('max_execution_time', '600');

        $data = $this->getData($count);
        $foundedProjects = [];
        $addedData = [];

        foreach ($data as $item) {
            //if sageAssignedData is existing the dataset will not be imported again
            if ($this->sageAssignedDataService->findBySageId($item['ID']) instanceof SageAssignedData) {
                continue;
            }
            $projects = $this->projectService->getProjectsByCostCenter($item['KstTraeger']);
            foreach ($projects as $project) {
                if (!in_array($project->id, $foundedProjects)) {
                    // check if project has a sage column in first table and create if not
                    $check = $project->table()->first()->columns()->where('type', 'sage')->exists();
                    if (!$check) {
                        // create column
                        $sageColumn = $this->columnService->createColumnInTable(
                            table:      $project->table()->first(),
                            name:       'Sage Abgleich',
                            subName:    '-',
                            type:       'sage'
                        );

                        $this->columnService->setColumnSubName(
                            table_id:   $project->table()->first()->id
                        );

                        $subPositionRows = SubPositionRow::query()->whereRelation(
                            'subPosition.mainPosition',
                            'table_id',
                            '=',
                            $project->table->id
                        )->get();

                        foreach ($subPositionRows as $subPositionRow) {
                            $subPositionRow->cells()->create([
                                'column_id' => $sageColumn->id,
                                'sub_position_row_id' => $subPositionRow->id,
                                'value' => 0,
                                'verified_value' => null,
                                'linked_money_source_id' => null,
                                'commented' => $subPositionRow->commented
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
                }
                $foundedKTO = ColumnCell::where('value', $item['SaKto'])->get();

                if ($foundedKTO->count() > 1) {
                    foreach ($foundedKTO as $kto) {
                        $foundedKST = ColumnCell::where('value', $item['KstStelle'])
                            ->where('sub_position_row_id', $kto->sub_position_row_id)->get();
                        if ($foundedKST->count() > 1) {
                            foreach ($foundedKST as $kst) {
                                SageNotAssignedData::query()
                                    ->where('sage_id', $item['ID'])
                                    ->firstOr(function () use ($item, $project): void {
                                        $this->sageNotAssignedDataService->createFromSageApiData($item, $project->id);
                                    });
                                $addedData[] = $item;
                            }
                        } else {
                            if ($kto->sub_position_row_id === $foundedKST->first()->sub_position_row_id) {
                                $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();
                                $sageColumnCell = $sageColumn->cells()
                                    ->where('column_id', $sageColumn->id)
                                    ->where('sub_position_row_id', $kto->sub_position_row_id)
                                    ->get()
                                    ->first();
                                $sageColumnCell->update(['value' => $item['Buchungsbetrag']]);

                                $this->sageAssignedDataService->createOrUpdateFromSageApiData(
                                    $sageColumnCell->id,
                                    $item,
                                    $project->id
                                );

                                $addedData[] = $item;
                            } else {
                                SageNotAssignedData::where('sage_id', $item['ID'])
                                    ->firstOr(function () use ($project, $item): void {
                                        $this->sageNotAssignedDataService->createFromSageApiData($item, $project->id);
                                    });
                                $addedData[] = $item;
                            }
                        }
                    }
                } else {
                    $singleKTO = $foundedKTO->first();
                    $foundedKST = ColumnCell::where('value', $item['KstStelle'])->first();
                    /** @var Column $sageColumn */
                    $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();

                    if ($singleKTO && $foundedKST) {
                        if ($singleKTO->sub_position_row_id === $foundedKST->sub_position_row_id) {
                            $sageColumnCell = $sageColumn->cells()
                                ->where('column_id', $sageColumn->id)
                                ->where('sub_position_row_id', $singleKTO->sub_position_row_id)
                                ->first();
                            $sageColumnCell->update(['value' => $item['Buchungsbetrag']]);

                            $this->sageAssignedDataService->createOrUpdateFromSageApiData(
                                $sageColumnCell->id,
                                $item,
                                $project->id
                            );

                            $addedData[] = $item;
                        } else {
                            SageNotAssignedData::where('sage_id', $item['ID'])
                                ->firstOr(function () use ($project, $item): void {
                                    $this->sageNotAssignedDataService->createFromSageApiData($item, $project->id);
                                });
                            $addedData[] = $item;
                        }
                    } else {
                        SageNotAssignedData::where('sage_id', $item['ID'])
                            ->firstOr(function () use ($project, $item): void {
                                $this->sageNotAssignedDataService->createFromSageApiData($item, $project->id);
                            });
                        $addedData[] = $item;
                    }
                }

                $foundedProjects[] = $project->id;
                if (!in_array($item, $addedData)) {
                    SageNotAssignedData::where('sage_id', $item['ID'])
                        ->firstOr(function () use ($project, $item): void {
                            $this->sageNotAssignedDataService->createFromSageApiData($item, $project->id);
                        });
                    $addedData[] = $item;
                }
            }

            // check if item is in addedData array and if not add it
            if (!in_array($item, $addedData)) {
                // check if item is in sage_not_assigned_data table and if not add it
                SageNotAssignedData::where('sage_id', $item['ID'])
                    ->firstOr(function () use ($item): void {
                        $this->sageNotAssignedDataService->createFromSageApiData($item);
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
        /** @var Project $project */
        $project = $table->project()->first();
        /** @var SageNotAssignedData $sageNotAssignedData */
        $sageNotAssignedData = SageNotAssignedData::find($request->sage_data_id);
        $sageColumn = $project->table()->first()->columns()->where('type', 'sage')->first();

        if (!$sageColumn instanceof Column) {
            $sageColumn = $this->columnService->createColumnInTable(
                table:      $project->table,
                name:       'Sage Abgleich',
                subName:    '-',
                type:       'sage'
            );

            $this->columnService->setColumnSubName(
                table_id:   $project->table()->first()->id
            );

            $subPositionRows = SubPositionRow::query()->whereRelation(
                'subPosition.mainPosition',
                'table_id',
                '=',
                $project->table->id
            )->get();

            /** @var SubPositionRow $subPositionRow */
            foreach ($subPositionRows as $subPositionRow) {
                $sageColumn->cells()->create([
                    'column_id' => $sageColumn->id,
                    'sub_position_row_id' => $subPositionRow->id,
                    'value' => 0,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                    'commented' => $subPositionRow->commented
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
                $subPositionRow->cells()->create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $subPositionRow->id,
                    'value' => $sageNotAssignedData->sa_kto,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]);
            } elseif ($column->name === 'KST') {
                $subPositionRow->cells()->create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $subPositionRow->id,
                    'value' => $sageNotAssignedData->kst_stelle,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]);
            } elseif ($column->name === 'Beschreibung') {
                $subPositionRow->cells()->create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $subPositionRow->id,
                    'value' => $sageNotAssignedData->buchungstext,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]);
            }
        }

        foreach ($columns as $column) {
            $subPositionRow->cells()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => 0,
                'verified_value' => null,
                'linked_money_source_id' => null,
            ]);
        }

        $sageColumnCell = ColumnCell::create([
            'column_id' => $sageColumn->id,
            'sub_position_row_id' => $subPositionRow->id,
            'value' => $sageNotAssignedData->buchungsbetrag,
            'commented' => $subPositionRow->commented,
            'linked_money_source_id' => null,
            'linked_type' => null,
            'verified_value' => null,
        ]);

        $this->sageAssignedDataService->createFromSageNotAssignedData(
            $sageColumnCell->id,
            $sageNotAssignedData
        );
        $this->sageNotAssignedDataService->forceDelete($sageNotAssignedData);
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
            $query['count'] = $count;
        }

        if ($desiredBookingDate = $this->sageApiSettingsService->getFirst()?->bookingDate) {
            $query['where'] = 'Buchungsdatum gt "' . Carbon::parse($desiredBookingDate)->format('d.m.Y') . '"';
        }

        $query['orderBy'] = 'Buchungsdatum asc';

        return $query;
    }
}
