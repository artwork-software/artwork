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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class Sage100Service
{
    private const FILTER_FIELD_BOOKINGDATE = 'Buchungsdatum';

    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ColumnService $columnService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService,
        private readonly SageApiSettingsService $sageApiSettingsService,
        private readonly SageAssignedDataService $sageAssignedDataService
    ) {
    }

    private function getData(int|null $count, string|null $specificDay)
    {
        return app(Sage100::class)->getData($this->buildQuery($count, $specificDay));
    }

    //@todo: fix phpcs error - fix complexity and nesting level
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function importDataToBudget(int|null $count, string|null $specificDay): int
    {
        //import php timeout 10 minutes
        ini_set('max_execution_time', '600');

        $addedData = [];

        foreach (($data = $this->getData($count, $specificDay)) as $item) {
            $sageAssignedData = $this->sageAssignedDataService->findBySageId($item['ID']);
            if ($sageAssignedData instanceof SageAssignedData) {
                $sageAssignedData->update([
                    'buchungsdatum' => $item['Buchungsdatum'],
                    'buchungsbetrag' => $item['Buchungsbetrag'],
                    'belegnummer' => $item['Belegnummer'],
                ]);

                //dataset already exists, no need to import again
                continue;
            }

            $sageNotAssignedData = $this->sageNotAssignedDataService->findBySageId($item['ID']);
            if ($sageNotAssignedData instanceof SageNotAssignedData) {
                $sageNotAssignedData->update([
                    'buchungsdatum' => $item['Buchungsdatum'],
                    'buchungsbetrag' => $item['Buchungsbetrag'],
                    'belegnummer' => $item['Belegnummer'],
                ]);
                //dataset already exists, no need to import again
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
            'value' => 0,
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
    private function buildQuery(int|null $count, string|null $specificDay): array
    {
        $query = [];

        if ($count) {
            $query['count'] = $count;
        }

        if ($specificDay) {
            $query['where'] = sprintf(
                '%s eq "%s"',
                self::FILTER_FIELD_BOOKINGDATE,
                Carbon::parse($specificDay)->format('d.m.Y')
            );
        } elseif ($desiredBookingDate = $this->sageApiSettingsService->getFirst()?->bookingDate) {
            $query['where'] = sprintf(
                '%s eq "%s" or %s gt "%s"',
                self::FILTER_FIELD_BOOKINGDATE,
                Carbon::parse($desiredBookingDate)->format('d.m.Y'),
                self::FILTER_FIELD_BOOKINGDATE,
                Carbon::parse($desiredBookingDate)->format('d.m.Y')
            );
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

    public function moveSageDataRow(ColumnCell $columnCell, ColumnCell $movedColumn, Request $request): RedirectResponse
    {
        $columnCells = $columnCell->subPositionRow->cells()->get();
        $movedColumnCells = $movedColumn->subPositionRow->cells()->get();
        if (
            $columnCells[0]->value === $movedColumnCells[0]->value &&
            $columnCells[1]->value === $movedColumnCells[1]->value
        ) {
            $assignedData = $movedColumn->sageAssignedData;
            foreach ($assignedData as $data) {
                if ($request->multiple === true) {
                    if (in_array($data->id, $request->selectedData)) {
                        $data->update(['column_cell_id' => $columnCell->id]);
                    }
                } else {
                    $data->update(['column_cell_id' => $columnCell->id]);
                }
            }
            $this->deleteMovedColumnCells($movedColumn);
        } else {
            return Redirect::back()->with(
                'error',
                __('flash-messages.budget-drag-and-drop.error.drop')
            );
        }
        return Redirect::back();
    }

    private function deleteMovedColumnCells(ColumnCell $movedColumn): void
    {
        $movedColumnCells = $movedColumn->subPositionRow->cells()->get();
        $sageAssignedData = $movedColumn->sageAssignedData()->get();
        if ($sageAssignedData->isEmpty()) {
            $movedColumnCells->each(function ($cell) use ($movedColumn): void {
                $cell->delete();
            });
            $movedColumn->subPositionRow->delete();
        }
    }

    public function moveSingleSageDataRowToNewRow(
        Request $request,
        Table $table,
        SubPosition $subPosition,
        int $positionBefore,
        ColumnCell $columnCell
    ): void {
        if ($request->multiple === false) {
            $project = $table->project;
            $columns = $table->columns()->whereNot('type', 'sage')->get();

            /** @var Column|null $sageColumn */
            $sageColumn = $table->columns->where('type', 'sage')->first();
            if (!$sageColumn instanceof Column) {
                $sageColumn = $this->createSageColumnForTable($project->table);
            }

            SubPositionRow::query()
                ->where('sub_position_id', $subPosition->id)
                ->where('position', '>', $positionBefore)
                ->increment('position');

            $subPositionRow = $subPosition->subPositionRows()->create([
                'commented' => false,
                'position' => $positionBefore + 1
            ]);

            $columns->each(function ($column, $index) use ($subPositionRow, $columnCell): void {
                $sageAssignedData = $columnCell->sageAssignedData->first();
                switch ($index) {
                    case 0:
                        $subPositionRow->cells()->create([
                            'column_id' => $column->id,
                            'sub_position_row_id' => $subPositionRow->id,
                            'value' => $sageAssignedData->sa_kto,
                            'verified_value' => null,
                            'linked_money_source_id' => null,
                        ]);
                        break;
                    case 1:
                        $subPositionRow->cells()->create([
                            'column_id' => $column->id,
                            'sub_position_row_id' => $subPositionRow->id,
                            'value' => $sageAssignedData->kst_stelle,
                            'verified_value' => null,
                            'linked_money_source_id' => null,
                        ]);
                        break;
                    case 2:
                        $subPositionRow->cells()->create([
                            'column_id' => $column->id,
                            'sub_position_row_id' => $subPositionRow->id,
                            'value' => $sageAssignedData->buchungstext,
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
            $sageAssignedData = $columnCell->sageAssignedData->first();
            $sageColumnCell = ColumnCell::create([
                'column_id' => $sageColumn->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => 0,
                'commented' => $subPositionRow->commented,
                'linked_money_source_id' => null,
                'linked_type' => null,
                'verified_value' => null,
            ]);

            $updated = $sageAssignedData->update(['column_cell_id' => $sageColumnCell->id]);
            if ($updated) {
                $this->deleteMovedColumnCells($columnCell);
            }
        }
    }


    public function moveMultipleSageDataRowToNewRow(
        Request $request,
        Table $table,
        SubPosition $subPosition,
        int $positionBefore,
        ColumnCell $columnCell
    ): void {
        $project = $table->project;
        $columns = $table->columns()->whereNot('type', 'sage')->get();

        /** @var Column|null $sageColumn */
        $sageColumn = $table->columns->where('type', 'sage')->first();
        if (!$sageColumn instanceof Column) {
            $sageColumn = $this->createSageColumnForTable($project->table);
        }

        SubPositionRow::query()
            ->where('sub_position_id', $subPosition->id)
            ->where('position', '>', $positionBefore)
            ->increment('position');

        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $positionBefore + 1
        ]);

        $columns->each(function ($column, $index) use ($subPositionRow, $columnCell, $request): void {
            $sageAssignedDataFirst = $columnCell->sageAssignedData->whereIn('id', $request->selectedData)->first();
            switch ($index) {
                case 0:
                    $subPositionRow->cells()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => $sageAssignedDataFirst->sa_kto,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                    ]);
                    break;
                case 1:
                    $subPositionRow->cells()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => $sageAssignedDataFirst->kst_stelle,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                    ]);
                    break;
                case 2:
                    $subPositionRow->cells()->create([
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => $sageAssignedDataFirst->buchungstext,
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
            'value' => 0,
            'commented' => $subPositionRow->commented,
            'linked_money_source_id' => null,
            'linked_type' => null,
            'verified_value' => null,
        ]);

        foreach ($columnCell->sageAssignedData->whereIn('id', $request->selectedData) as $sageAssignedData) {
            $sageAssignedData->update(['column_cell_id' => $sageColumnCell->id]);
        }
        $this->deleteMovedColumnCells($columnCell);
    }
}
