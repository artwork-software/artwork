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
                            $sageColumnCell->update(['value' => $item['Buchungsbetrag']]);

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
}
