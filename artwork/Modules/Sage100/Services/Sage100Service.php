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
use Illuminate\Http\Request;

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

    private function getData(int|null $count)
    {
        return app(Sage100::class)->getData($this->buildQuery($count));
    }

    //@todo: fix phpcs error - fix complexity and nesting level
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function importDataToBudget(int|null $count): int
    {
        //import php timeout 10 minutes
        ini_set('max_execution_time', '600');

        $addedData = [];

        foreach (($data = $this->getData($count)) as $item) {
            //if sageAssignedData is existing the dataset will not be imported again
            if ($this->sageAssignedDataService->findBySageId($item['ID']) instanceof SageAssignedData) {
                continue;
            }
            $projects = $this->projectService->getProjectsByCostCenter($item['KstTraeger']);
            /** @var Project $project */
            foreach ($projects as $project) {
                    /** @var Column|null $sageColumn */
                    $sageColumn = $project->table->columns->where('type', 'sage')->first();
                if (!$sageColumn instanceof Column) {
                    $sageColumn = $this->createSageColumnForTable($project->table);
                }

                $foundKTOs = ColumnCell::where('value', $item['SaKto'])->get();
                if ($foundKTOs->count() > 1) {
                    foreach ($foundKTOs as $foundKTO) {
                        $foundKSTs = ColumnCell::where('value', $item['KstStelle'])
                            ->where('sub_position_row_id', $foundKTO->sub_position_row_id)->get();
                        if ($foundKSTs->count() > 1) {
                            foreach ($foundKSTs as $kst) {
                                SageNotAssignedData::query()
                                    ->where('sage_id', $item['ID'])
                                    ->firstOr(function () use ($item, $project): void {
                                        $this->sageNotAssignedDataService->createFromSageApiData(
                                            $item,
                                            $project->id
                                        );
                                    });
                                $addedData[] = $item;
                            }
                        } else {
                            if ($foundKTO->sub_position_row_id === $foundKSTs->first()->sub_position_row_id) {
                                $sageColumnCell = $sageColumn->cells()
                                    ->where('column_id', $sageColumn->id)
                                    ->where('sub_position_row_id', $foundKTO->sub_position_row_id)
                                    ->first();
                                if (
                                    $sageColumnCell->value === 0 || $sageColumnCell->value === null
                                    || $sageColumnCell->value === '' || $sageColumnCell->value === '0'
                                ) {
                                    $sageColumnCell->update(['value' => $item['Buchungsbetrag']]);
                                }

                                $this->sageAssignedDataService->createOrUpdateFromSageApiData(
                                    $sageColumnCell->id,
                                    $item,
                                    $project->id
                                );

                                $addedData[] = $item;
                            } else {
                                SageNotAssignedData::where('sage_id', $item['ID'])
                                    ->firstOr(function () use ($project, $item): void {
                                        $this->sageNotAssignedDataService->createFromSageApiData(
                                            $item,
                                            $project->id
                                        );
                                    });
                                $addedData[] = $item;
                            }
                        }
                    }
                } else {
                    $foundKTO = $foundKTOs->first();
                    $foundKST = ColumnCell::where('value', $item['KstStelle'])->first();

                    if ($foundKTO && $foundKST) {
                        if ($foundKTO->sub_position_row_id === $foundKST->sub_position_row_id) {
                            $sageColumnCell = $sageColumn->cells()
                                ->where('column_id', $sageColumn->id)
                                ->where('sub_position_row_id', $foundKTO->sub_position_row_id)
                                ->first();
                            if (
                                $sageColumnCell->value === 0 || $sageColumnCell->value === null
                                || $sageColumnCell->value === '' || $sageColumnCell->value === '0'
                            ) {
                                $sageColumnCell->update(['value' => $item['Buchungsbetrag']]);
                            }
                            //$sageColumnCell->update(['value' => $item['Buchungsbetrag']]);

                            $this->sageAssignedDataService->createOrUpdateFromSageApiData(
                                $sageColumnCell->id,
                                $item,
                                $project->id
                            );
                        } else {
                            SageNotAssignedData::where('sage_id', $item['ID'])
                                ->firstOr(function () use ($project, $item): void {
                                    $this->sageNotAssignedDataService->createFromSageApiData($item, $project->id);
                                });
                        }
                        $addedData[] = $item;
                    } else {
                        SageNotAssignedData::where('sage_id', $item['ID'])
                            ->firstOr(function () use ($project, $item): void {
                                $this->sageNotAssignedDataService->createFromSageApiData($item, $project->id);
                            });
                        $addedData[] = $item;
                    }
                }

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
            $this->updateSageApiSettingsBookingDateFromData($data);
        }
        return 0;
    }

    private function updateSageApiSettingsBookingDateFromData(array $data): void
    {
        $lastDataset = array_pop($data);
        if (isset($lastDataset['Buchungsdatum'])) {
            $this->sageApiSettingsService->updateBookingDate(Carbon::parse($lastDataset['Buchungsdatum']));
        }
    }

    public function dropData($request): void
    {
        /** @var Table $table */
        $table = Table::find($request->table_id);
        $columns = $table->columns()->whereNot('type', 'sage')->get();

        $subPosition = SubPosition::find($request->sub_position_id);
        $project = $table->project;
        /** @var SageNotAssignedData $sageNotAssignedData */
        $sageNotAssignedData = SageNotAssignedData::find($request->sage_data_id);

        /** @var Column|null $sageColumn */
        $sageColumn = $table->columns->where('type', 'sage')->first();
        if (!$sageColumn instanceof Column) {
            $sageColumn = $this->createSageColumnForTable($project->table);
        }

        SubPositionRow::query()
            ->where('sub_position_id', $request->sub_position_id)
            ->where('position', '>', $request->positionBefore)
            ->increment('position');

        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $request->positionBefore + 1
        ]);

        $columns->each(function ($column, $index) use ($subPositionRow, $sageNotAssignedData): void {
            switch ($index) {
                case 0:
                    $subPositionRow->cells()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => $sageNotAssignedData->sa_kto,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                    ]);
                    break;
                case 1:
                    $subPositionRow->cells()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => $sageNotAssignedData->kst_stelle,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                    ]);
                    break;
                case 2:
                    $subPositionRow->cells()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => $sageNotAssignedData->buchungstext,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                    ]);
                    break;
                default:
                    $subPositionRow->cells()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => 0,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                    ]);
            }
        });

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

    private function createSageColumnForTable(Table $table): Column
    {
        $sageColumn = $this->columnService->createColumnInTable($table, 'Sage Abgleich', '-', 'sage');

        $this->columnService->setColumnSubName($table->id);

        $table->mainPositions->each(function (MainPosition $mainPosition) use ($sageColumn): void {
            $mainPosition->subPositions->each(function (SubPosition $subPosition) use ($sageColumn): void {
                $subPosition->subPositionRows->each(function (SubPositionRow $subPositionRow) use ($sageColumn): void {
                    $subPositionRow->cells()->create([
                        'column_id' => $sageColumn->id,
                        'value' => 0,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                        'commented' => $subPositionRow->commented
                    ]);
                });

                $sageColumn->subPositionSumDetails()->create(['sub_position_id' => $subPosition->id]);
            });

            $sageColumn->mainPositionSumDetails()->create(['main_position_id' => $mainPosition->id]);
        });

        $sageColumn->budgetSumDetails()->create([
            'type' => 'COST'
        ]);

        $sageColumn->budgetSumDetails()->create([
            'type' => 'EARNING'
        ]);

        return $sageColumn;
    }

    public function moveSageDataRow(ColumnCell $columnCell, ColumnCell $movedColumn, Request $request): void
    {
        // get all Cells on the same row as $columnCell
        $columnCells = $columnCell->subPositionRow->cells()->get();

        // get all Cells on the same row as $movedColumn
        $movedColumnCells = $movedColumn->subPositionRow->cells()->get();

        // check if the first two cells in $columnCells and $movedColumnCells have the same value
        if (
            $columnCells[0]->value === $movedColumnCells[0]->value &&
            $columnCells[1]->value === $movedColumnCells[1]->value
        ) {
            // attach the $movedColumn->sageAssignedData to $columnCell
            $assignedData = $movedColumn->sageAssignedData;
            foreach ($assignedData as $data) {
                if ($request->multiple === true) {
                    // only move the data if the request is for multiple rows
                    if (in_array($data->id, $request->selectedData)) {
                        $data->update(['column_cell_id' => $columnCell->id]);

                        // update the value of the $columnCell with the value of the $data->buchungsbetrag
                        $this->addValueToColumnCell($columnCell, $data->buchungsbetrag);
                        // subtract the value of the $data->buchungsbetrag from the $movedColumn
                        $this->subtractValueFromColumnCell($movedColumn, $data->buchungsbetrag);
                    }
                } else {
                    $data->update(['column_cell_id' => $columnCell->id]);

                    // update the value of the $columnCell with the value of the $data->buchungsbetrag
                    $this->addValueToColumnCell($columnCell, $data->buchungsbetrag);
                    // subtract the value of the $data->buchungsbetrag from the $movedColumn
                    $this->subtractValueFromColumnCell($movedColumn, $data->buchungsbetrag);
                }
            }

            // if all cells in $movedColumnCells has the value "0" than delete the $movedColumn
            // and all cells in the same row and delete the row itself
            $this->deleteMovedColumnCells($movedColumn);
        }
    }

    private function subtractValueFromColumnCell(ColumnCell $columnCell, float $value): void
    {
        $columnCell->update(['value' => $columnCell->value - $value]);
    }

    private function addValueToColumnCell(ColumnCell $columnCell, float $value): void
    {
        $columnCell->update(['value' => $columnCell->value + $value]);
    }

    private function deleteMovedColumnCells(ColumnCell $movedColumn): void
    {
        $movedColumnCells = $movedColumn->subPositionRow->cells()->get();
        if ($movedColumnCells->slice(3)->every(fn (ColumnCell $cell) => $cell->value === "0")) {
            $movedColumnCells->each(fn (ColumnCell $cell) => $cell->delete());
            $movedColumn->delete();
            $movedColumn->subPositionRow->delete();
        }
    }
}
