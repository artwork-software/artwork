<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\DTOs\MatchRelevantProjectGroupDTO;
use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\BudgetColumnSetting\Services\BudgetColumnSettingService;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\ContractType\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudgetService
{
    public function __construct(
        private readonly ColumnService $columnService,
        private readonly MainPositionService $mainPositionService,
        private readonly BudgetColumnSettingService $columnSettingService,
        private readonly TableService $tableService,
        private readonly SageAssignedDataCommentService $sageAssignedDataCommentService,
        private readonly SageApiSettingsService $sageApiSettingsService
    )
    {
    }

    public function generateBasicBudgetValues(
        Project $project,
    ): void {
        DB::transaction(function () use (
            $project,
        ): void {
            $table = $this->tableService->createTableInProject(
                $project,
                $project->name . ' Budgettabelle',
                false
            );

            $columns = new Collection();

            $columns[] = $this->columnService->createColumnInTable(
                table: $table,
                name: $this->columnSettingService->getColumnNameByColumnPosition(0),
                subName: '',
                type: 'empty',
                position: 0
            );

            $columns[] = $this->columnService->createColumnInTable(
                table: $table,
                name: $this->columnSettingService->getColumnNameByColumnPosition(1),
                subName: '',
                type: 'empty',
                position: 1
            );

            $columns[] = $this->columnService->createColumnInTable(
                table: $table,
                name: $this->columnSettingService->getColumnNameByColumnPosition(2),
                subName: '',
                type: 'empty',
                position: 2
            );

            $columns[] = $this->columnService->createColumnInTable(
                table: $table,
                name: date('Y') . ' €',
                subName: 'A',
                type: 'empty',
                position: 3,
                relevant_for_project_groups: !$project->is_group,
            );

            if ($project->is_group){
                $columns[] = $this->columnService->createColumnInTable(
                    table: $table,
                    name: 'Unterprojekte',
                    subName: '-',
                    type: 'project_relevant_column',
                    position: 100
                );
            }

            $costMainPosition = $this->mainPositionService->createMainPosition(
                table: $table,
                budgetTypesEnum: BudgetTypeEnum::BUDGET_TYPE_COST,
                name: 'Hauptpostion',
                position: $table->mainPositions()
                    ->where('type', BudgetTypeEnum::BUDGET_TYPE_COST)
                    ->max('position') + 1
            );

            $earningMainPosition = $this->mainPositionService->createMainPosition(
                table: $table,
                budgetTypesEnum: BudgetTypeEnum::BUDGET_TYPE_EARNING,
                name: 'Hauptpostion',
                position: $table->mainPositions()
                    ->where('type', BudgetTypeEnum::BUDGET_TYPE_EARNING)
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
                    'value' => '0,00',
                    'verified_value' => "",
                    'linked_money_source_id' => null,
                ]);
                $earningSubPositionRow->cells()->create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $earningSubPositionRow->id,
                    'value' => '0,00',
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

    /**
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getBudgetForProjectTab(
        Project $project,
        array $loadedProjectInformation,
    ): array {
        $table = $project->table()->first();

        //Failsave for projects without table
        if (!$table) {
            //Reporting so we know if this happens
            report(new \RuntimeException('Project has no table'));
            $this->generateBasicBudgetValues($project);
            $project->table()->first();
        }

        $columns = $table->columns()->get();

        $calculateNames = [];
        foreach ($columns as $column) {
            $calculateName = '';
            if ($column->type === 'difference' || $column->type === 'sum') {
                $firstName = Column::where('id', $column->linked_first_column)->first()?->subName;
                $secondName = Column::where('id', $column->linked_second_column)->first()?->subName;
                if ($column->type === 'difference') {
                    $calculateName = $firstName . ' - ' . $secondName;
                } else {
                    $calculateName = $firstName . ' + ' . $secondName;
                }
            }
            $calculateNames[$column->id] = $calculateName;
        }

        $selectedCell = request('selectedCell')
            ? ColumnCell::find(request('selectedCell'))
            : null;

        $selectedRow = request('selectedRow')
            ? SubPositionRow::find(request('selectedRow'))
            : null;

        $templates = Table::where('is_template', true)->get();

        $selectedSumDetail = null;

        if (request('selectedSubPosition') && request('selectedColumn')) {
            $selectedSumDetail = Collection::make(
                SubPositionSumDetail::with(['comments.user', 'sumMoneySource.moneySource'])
                    ->where('sub_position_id', request('selectedSubPosition'))
                    ->where('column_id', request('selectedColumn'))
                    ->first()
            )->merge(['class' => SubPositionSumDetail::class]);
        }

        if (request('selectedMainPosition') && request('selectedColumn')) {
            $selectedSumDetail = Collection::make(
                MainPositionDetails::with(['comments.user', 'sumMoneySource.moneySource'])
                    ->where('main_position_id', request('selectedMainPosition'))
                    ->where('column_id', request('selectedColumn'))
                    ->first()
            )->merge(['class' => MainPositionDetails::class]);
        }

        if (request('selectedBudgetType') && request('selectedColumn')) {
            $selectedSumDetail = Collection::make(
                BudgetSumDetails::with(['comments.user', 'sumMoneySource.moneySource'])
                    ->where('type', request('selectedBudgetType'))
                    ->where('column_id', request('selectedColumn'))
                    ->first()
            )->merge(['class' => BudgetSumDetails::class]);
        }

        //load commented budget items setting for given user
        Auth::user()->load(['commentedBudgetItemsSetting']);
        $projectsGroup = collect();
        $globalGroup = collect();

        if ($this->sageApiSettingsService->getFirst()?->enabled) {
            $sageNotAssigned = SageNotAssignedData::query()
                ->where('project_id', $project->id)
                ->orWhere('project_id', null)
                ->orderBy('buchungsdatum', 'desc')
                ->get();

            $sageNotAssigned->each(function ($item) use ($projectsGroup, $globalGroup, $project): void {
                if ($item->project_id === null) {
                    $globalGroup->push($item);
                } elseif ($item->project_id === $project->id) {
                    $projectsGroup->push($item);
                }
            });
        }

        $groupedProjectData = [];
        $existingEntries = [];

        if ($project->is_group) {
            $groupProjects = $project->projectsOfGroup;
            $groupColumns = $project->table()->first()?->columns()->get() ?? collect();
            $firstTwoGroupColumns = $groupColumns->sortBy('position')->take(2);


            if ($firstTwoGroupColumns->count() < 2) {
                return $loadedProjectInformation;
            }

            [$firstGroupColumn, $secondGroupColumn] = [$firstTwoGroupColumns->first(), $firstTwoGroupColumns->last()];

            foreach ($project->table()->first()?->mainPositions ?? [] as $groupMainPosition) {
                foreach ($groupMainPosition->subPositions ?? [] as $groupSubPosition) {
                    foreach ($groupSubPosition->subPositionRows ?? [] as $groupRow) {
                        $groupFirstValue = trim((string) ($groupRow->cells()->where('column_id', $firstGroupColumn->id)->first()?->value ?? ''));
                        $groupSecondValue = trim((string) ($groupRow->cells()->where('column_id', $secondGroupColumn->id)->first()?->value ?? ''));

                        if ($groupFirstValue === '' || $groupSecondValue === '') {
                            continue;
                        }

                        foreach ($groupProjects as $subProject) {
                            $subProjectColumns = $subProject->table()->first()?->columns()->get() ?? collect();
                            $firstTwoSubColumns = $subProjectColumns->sortBy('position')->take(2);


                            if ($firstTwoSubColumns->count() < 2) {
                                continue;
                            }

                            [$firstSubColumn, $secondSubColumn] = [$firstTwoSubColumns->first(), $firstTwoSubColumns->last()];
                            $relevantColumns = $subProjectColumns->where('relevant_for_project_groups', true);

                            foreach ($subProject->table()->first()?->mainPositions ?? [] as $subMainPosition) {
                                foreach ($subMainPosition->subPositions ?? [] as $subPosition) {
                                    foreach ($subPosition->subPositionRows ?? [] as $subRow) {
                                        $subFirstValue = trim((string) ($subRow->cells()->where('column_id', $firstSubColumn->id)->first()?->value ?? ''));
                                        $subSecondValue = trim((string) ($subRow->cells()->where('column_id', $secondSubColumn->id)->first()?->value ?? ''));

                                        if ($groupFirstValue === $subFirstValue && $groupSecondValue === $subSecondValue) {
                                            if ($groupMainPosition->type !== $subMainPosition->type) {
                                                continue;
                                            }

                                            foreach ($relevantColumns as $relevantColumn) {
                                                $relevantValue = $subRow->cells()->where('column_id', $relevantColumn->id)->first()?->value ?? 0;
                                                $cellId = $subRow->cells()->where('column_id', $relevantColumn->id)->first()?->id;

                                                if (!$cellId) {
                                                    continue;
                                                }

                                                $uniqueKey = $cellId . '-' . $relevantColumn->id;

                                                if (!isset($existingEntries[$uniqueKey])) {
                                                    $dtoObject = new MatchRelevantProjectGroupDTO(
                                                        $subProject->id,
                                                        $subProject->name,
                                                        $groupRow->id,
                                                        $groupRow->name,
                                                        $groupSubPosition->id,
                                                        $groupMainPosition->id,
                                                        $relevantColumn->id,
                                                        $relevantColumn->name,
                                                        $relevantValue,
                                                        $cellId,
                                                        $subMainPosition->type,
                                                        $groupRow->commented,
                                                        $subFirstValue,
                                                        $subSecondValue
                                                    );

                                                    if ($subMainPosition->type === 'BUDGET_TYPE_EARNING') {
                                                        $groupedProjectData['BUDGET_TYPE_EARNING'][] = $dtoObject;
                                                    } else {
                                                        $groupedProjectData['BUDGET_TYPE_COST'][] = $dtoObject;
                                                    }

                                                    $existingEntries[$uniqueKey] = true;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $loadedProjectInformation['BudgetTab'] = [
            'moneySources' => MoneySource::all(),
            'budget' => [
                'table' => $project->table()
                    ->with([
                        'columns' => function (HasMany $query): void {
                            $query->orderBy('position');
                            $query->orderByRaw('CASE WHEN type = "sage" THEN 1 ELSE 0 END');
                        },
                        'mainPositions',
                        'mainPositions.verified',
                        'mainPositions.subPositions' => function ($query) {
                            return $query->orderBy('position');
                        },
                        'mainPositions.subPositions.verified',
                        'mainPositions.subPositions.subPositionRows' => function ($query) {
                            return $query->orderBy('position');
                        },
                        'mainPositions.subPositions.subPositionRows.cells' => function (HasMany $query): void {
                            $query
                                ->with([
                                    'sageAssignedData',
                                    'sageAssignedData.comments' => function (HasMany $hasMany): HasMany {
                                        return $hasMany->orderBy('created_at', 'desc');
                                    },
                                    'sageAssignedData.comments.user'
                                ])
                                // sage cells should be at the end
                                ->join('columns', 'column_sub_position_row.column_id', '=', 'columns.id')
                                ->orderBy('position')
                                ->orderByRaw('CASE WHEN type = "sage" THEN 1 ELSE 0 END')
                                ->select('column_sub_position_row.*')
                                ->withCount('comments')
                                ->withCount(['calculations' => function ($query) {
                                    // count if value is not 0
                                    return $query->where('value', '!=', 0);
                                }]);
                        },
                        'mainPositions.subPositions.subPositionRows.cells.column',
                    ])
                    ->first(),
                'selectedCell' => $selectedCell?->load(['calculations' => function ($calculations): void {
                    $calculations->orderBy('position', 'asc');
                }, 'comments.user', 'comments', 'column' => function ($query): void {
                    $query->orderBy('created_at', 'desc');
                }]),
                'selectedSumDetail' => $selectedSumDetail,
                'selectedRow' => $selectedRow?->load(['comments.user', 'comments' => function ($query): void {
                    $query->orderBy('created_at', 'desc');
                }]),
                'templates' => $templates,
                'columnCalculatedNames' => $calculateNames,
            ],
            'projectMoneySources' => $project->moneySources()->get(),
            'contractTypes' => ContractType::all()->toArray(),
            'companyTypes' => CompanyType::all()->toArray(),
            'currencies' => Currency::all()->toArray(),
            'collectingSocieties' => CollectingSociety::all()->toArray(),
            'sageNotAssigned' => [
                'projectsGroup' => $projectsGroup,
                'globalGroup' => $globalGroup
            ],
            'recentlyCreatedSageAssignedDataComment' => $this->determineRecentlyCreatedSageAssignedDataComment(),
            'projectGroupRelevantBudgetData' => $groupedProjectData
        ];

        return $loadedProjectInformation;
    }


    private function determineRecentlyCreatedSageAssignedDataComment(
    ): SageAssignedDataComment|null {
        //if there's a recently created comment for any SageAssignedData-Models retrieve corresponding model by id
        //to display it right after the request finished without reopening the SageAssignedDataModal
        $recentlyCreatedSageAssignedDataComment = null;

        if ($recentlyCreatedSageAssignedDataCommentId = session('recentlyCreatedSageAssignedDataCommentId')) {
            $recentlyCreatedSageAssignedDataComment = $this->sageAssignedDataCommentService->getById(
                $recentlyCreatedSageAssignedDataCommentId
            );
        }

        if ($recentlyCreatedSageAssignedDataComment instanceof SageAssignedDataComment) {
            //load corresponding user for UserPopoverTooltip
            $recentlyCreatedSageAssignedDataComment->load('user');
        }

        return $recentlyCreatedSageAssignedDataComment;
    }
}
