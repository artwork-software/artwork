<?php

namespace Artwork\Modules\Sage100\Services;

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
use Artwork\Modules\Sage100\Clients\Sage100Client;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

readonly class Sage100Service
{
    private const FILTER_FIELD_BOOKINGDATE = 'Buchungsdatum';

    public function importDataToBudget(
        ?int $count,
        ?string $specificDay,
        ProjectService $projectService,
        ColumnService $columnService,
        SageApiSettingsService $sageApiSettingsService,
        SageAssignedDataService $sageAssignedDataService,
        SageNotAssignedDataService $sageNotAssignedDataService
    ): int {
        //import php timeout 10 minutes
        ini_set('max_execution_time', '600');

        foreach (($data = $this->getData($count, $specificDay, $sageApiSettingsService)) as $item) {
            if ($this->updateExistingSageAssignedDataIfExists($item, $sageAssignedDataService)) {
                continue;
            }

            $sageNotAssignedData = $this->updateExistingSageNotAssignedDataIfExists($item, $sageNotAssignedDataService);

            //KstTrager (Kostenstelle) is unique and exists only in one Project, find it
            $project = $projectService->getProjectByCostCenter($item['KstTraeger']);
            if (is_null($project)) {
                //create project unrelated SageNotAssignedData if no Project is found
                $this->createSageNotAssignedData($item, $sageNotAssignedDataService);

                continue;
            }

            //find SubPositionRows containing ColumnCells with SaKto (KTO) and KstStelle (KST)
            $subPositionRows = $this->findSubPositionRowsByKtoAndKst(
                $item['SaKto'],
                $item['KstStelle'],
                $project
            );

            //create project related SageNotAssignedData if not exactly one SubPositionRow
            if ($subPositionRows->count() !== 1) {
                $this->createSageNotAssignedData($item, $sageNotAssignedDataService, $project->id);

                continue;
            }

            /** @var Column|null $sageColumn */
            $sageColumn = $project->table->columns->where('type', 'sage')->first();
            if (!$sageColumn instanceof Column) {
                $sageColumn = $this->createSageColumnForTable($project->table, $columnService);
            }

            $subPositionRowsSageColumnCellId = $sageColumn
                ->cells
                ->where('sub_position_row_id', $subPositionRows->first()->id)
                ->first()
                ->id;

            //check if SageNotAssignedData exists, if so create SageAssignedData from it
            if ($sageNotAssignedData instanceof SageNotAssignedData) {
                $sageAssignedDataService->createFromSageNotAssignedData(
                    $subPositionRowsSageColumnCellId,
                    $sageNotAssignedData,
                    $sageNotAssignedDataService
                );

                continue;
            }

            //otherwise create a new SageAssignedData entity
            $sageAssignedDataService->createFromSageApiData(
                $subPositionRowsSageColumnCellId,
                $item
            );
        }

        //if data was imported update import date from latest given booking-date (Buchungsdatum)
        if (!empty($data)) {
            $this->updateSageApiSettingsBookingDateFromData($data, $sageApiSettingsService);
        }

        return 0;
    }

    public function dropData(
        Request $request,
        ColumnService $columnService,
        SageAssignedDataService $sageAssignedDataService,
        SageNotAssignedDataService $sageNotAssignedDataService
    ): void {
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
            $sageColumn = $this->createSageColumnForTable($project->table, $columnService);
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
                        'value' => '0,00',
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

        $sageAssignedDataService->createFromSageNotAssignedData(
            $sageColumnCell->id,
            $sageNotAssignedData,
            $sageNotAssignedDataService
        );
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

    public function moveSingleSageDataRowToNewRow(
        Request $request,
        Table $table,
        SubPosition $subPosition,
        int $positionBefore,
        ColumnCell $columnCell,
        ColumnService $columnService
    ): void {
        if ($request->multiple === false) {
            $project = $table->project;
            $columns = $table->columns()->whereNot('type', 'sage')->get();

            /** @var Column|null $sageColumn */
            $sageColumn = $table->columns->where('type', 'sage')->first();
            if (!$sageColumn instanceof Column) {
                $sageColumn = $this->createSageColumnForTable($project->table, $columnService);
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
                            'value' => '0,00',
                            'verified_value' => null,
                            'linked_money_source_id' => null,
                        ]);
                }
            });
            /** @var SageAssignedData|null $sageAssignedData */
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
        ColumnCell $columnCell,
        ColumnService $columnService
    ): void {
        $project = $table->project;
        $columns = $table->columns()->whereNot('type', 'sage')->get();

        /** @var Column|null $sageColumn */
        $sageColumn = $table->columns->where('type', 'sage')->first();
        if (!$sageColumn instanceof Column) {
            $sageColumn = $this->createSageColumnForTable($project->table, $columnService);
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
                        'value' => '0,00',
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

    private function findSubPositionRowsByKtoAndKst(string $kto, string $kst, Project $project): Collection
    {
        $subPositionRows = Collection::make();

        $project->table->mainPositions->each(
            function (MainPosition $mainPosition) use ($kto, $kst, $subPositionRows): void {
                $mainPosition->subPositions->each(
                    function (SubPosition $subPosition) use ($kto, $kst, $subPositionRows): void {
                        $subPosition->subPositionRows->each(
                            function (SubPositionRow $subPositionRow) use ($kto, $kst, $subPositionRows): void {
                                if (
                                    $subPositionRow->cells->where('value', $kto)->first() &&
                                    $subPositionRow->cells->where('value', $kst)->first()
                                ) {
                                    $subPositionRows->push($subPositionRow);
                                }
                            }
                        );
                    }
                );
            }
        );

        return $subPositionRows;
    }

    private function updateExistingSageAssignedDataIfExists(
        array $item,
        SageAssignedDataService $sageAssignedDataService
    ): bool {
        $sageAssignedData = $sageAssignedDataService->findBySageId($item['ID']);

        if (is_null($sageAssignedData)) {
            return false;
        }

        $sageAssignedDataService->update(
            $sageAssignedData,
            [
                'buchungsdatum' => $item['Buchungsdatum'],
                'buchungsbetrag' => $item['Buchungsbetrag'],
                'belegnummer' => $item['Belegnummer'],
            ]
        );

        //dataset already assigned, no need to import again or check if it can be assigned
        return true;
    }

    private function updateExistingSageNotAssignedDataIfExists(
        array $item,
        SageNotAssignedDataService $sageNotAssignedDataService
    ): SageNotAssignedData|null {
        $sageNotAssignedData = $sageNotAssignedDataService->findBySageId($item['ID']);

        if ($sageNotAssignedData instanceof SageNotAssignedData) {
            $sageNotAssignedDataService->update(
                $sageNotAssignedData,
                [
                    'buchungsdatum' => $item['Buchungsdatum'],
                    'buchungsbetrag' => $item['Buchungsbetrag'],
                    'belegnummer' => $item['Belegnummer'],
                ]
            );
        }

        return $sageNotAssignedData;
    }

    private function createSageNotAssignedData(
        array $item,
        SageNotAssignedDataService $sageNotAssignedDataService,
        ?int $projectId = null
    ): void {
        SageNotAssignedData::query()
            ->where('sage_id', $item['ID'])
            ->existsOr(
                function () use ($sageNotAssignedDataService, $item, $projectId): void {
                    $sageNotAssignedDataService->createFromSageApiData(
                        $item,
                        $projectId
                    );
                }
            );
    }

    private function updateSageApiSettingsBookingDateFromData(
        array $data,
        SageApiSettingsService $sageApiSettingsService
    ): void {
        $lastDataset = array_pop($data);
        if (isset($lastDataset['Buchungsdatum'])) {
            $sageApiSettingsService->updateBookingDate(Carbon::parse($lastDataset['Buchungsdatum']));
        }
    }

    private function getData(int|null $count, string|null $specificDay, SageApiSettingsService $sageApiSettingsService)
    {
        return app(Sage100Client::class)->getData($this->buildQuery($count, $specificDay, $sageApiSettingsService));
    }

    /**
     * @return array<string, string>
     */
    private function buildQuery(
        int|null $count,
        string|null $specificDay,
        SageApiSettingsService $sageApiSettingsService
    ): array {
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
        } elseif ($desiredBookingDate = $sageApiSettingsService->getFirst()?->bookingDate) {
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

    private function createSageColumnForTable(Table $table, ColumnService $columnService): Column
    {
        $sageColumn = $columnService->createColumnInTable($table, 'Sage Abgleich', '-', 'sage');

        $columnService->setColumnSubName($table->id);

        $table->mainPositions->each(function (MainPosition $mainPosition) use ($sageColumn): void {
            $mainPosition->subPositions->each(function (SubPosition $subPosition) use ($sageColumn): void {
                $subPosition->subPositionRows->each(function (SubPositionRow $subPositionRow) use ($sageColumn): void {
                    $subPositionRow->cells()->create([
                        'column_id' => $sageColumn->id,
                        'value' => '0,00',
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
}
