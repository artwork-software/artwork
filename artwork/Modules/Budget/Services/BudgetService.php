<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\DTOs\MatchRelevantProjectGroupDTO;
use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetColumnSettingService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function __construct(
        private readonly ColumnService $columnService,
        private readonly MainPositionService $mainPositionService,
        private readonly BudgetColumnSettingService $columnSettingService,
        private readonly TableService $tableService,
        private readonly SageAssignedDataCommentService $sageAssignedDataCommentService,
        private readonly SageApiSettingsService $sageApiSettingsService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService,
        private readonly BudgetCacheService $budgetCacheService,
        private readonly BudgetSumCalculator $budgetSumCalculator
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

            // Auch Projektgruppen erhalten eine budgetrelevante Spalte (Vorschau-Spalte für Exporte).
            $columns[] = $this->columnService->createColumnInTable(
                table: $table,
                name: date('Y') . ' €',
                subName: 'A',
                type: 'empty',
                position: 3,
                relevant_for_project_groups: true,
            );

            if ($project->is_group){
                $columns[] = $this->columnService->createColumnInTable(
                    table: $table,
                    name: 'Unterprojekte',
                    subName: '-',
                    type: 'subprojects_column_for_group',
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
                'position' => $costSubPosition->subPositionRows()->max('position') + 1,
                'order' => $costSubPosition->subPositionRows()->max('order') + 1,
            ]);

            $earningSubPositionRow = $earningSubPosition->subPositionRows()->create([
                'commented' => false,
                'position' => $earningSubPosition->subPositionRows()->max('position') + 1,
                'order' => $earningSubPosition->subPositionRows()->max('order') + 1,

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
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getBudgetForProjectTab(
        Project $project,
        array $loadedProjectInformation,
    ): array {
        $table = $project->table()->first();

        //Failsafe for projects without table
        if (!$table) {
            report(new \RuntimeException('Project has no table'));
            $this->generateBasicBudgetValues($project);
            $table = $project->table()->first();
        }

        // Try loading from Redis cache
        $cachedPayload = $this->budgetCacheService->getBudgetPayload($project->id);

        if ($cachedPayload === null) {
            $cachedPayload = $this->buildBudgetPayload($project, $table);
            $this->budgetCacheService->setBudgetPayload($project->id, $cachedPayload);
        }

        $table = $cachedPayload['table'];

        // Request-specific data (always fresh)
        $selectedCell = request('selectedCell')
            ? ColumnCell::find(request('selectedCell'))
            : null;

        $selectedRow = request('selectedRow')
            ? SubPositionRow::find(request('selectedRow'))
            : null;

        $templates = $this->budgetCacheService->getTemplates();

        $selectedSumDetail = $this->resolveSelectedSumDetail();

        //load commented budget items setting for given user
        Auth::user()->load(['commentedBudgetItemsSetting', 'budgetAccountDisplaySetting']);

        $sageNotAssigned = $this->resolveSageNotAssigned($project);

        $staticLookups = $this->budgetCacheService->getStaticLookups();

        $loadedProjectInformation['BudgetTab'] = [
            'moneySources' => $staticLookups['moneySources'],
            'budget' => [
                'table' => $table,
                'selectedCell' => $selectedCell?->load([
                    'calculations' => function ($calculations): void {
                        $calculations->orderBy('position', 'asc');
                    },
                    'comments.user',
                    'comments',
                    'column' => function ($query): void {
                        $query->orderBy('created_at', 'desc');
                    }
                ])->loadMissing('linkedMoneySource'),
                'selectedSumDetail' => $selectedSumDetail,
                'selectedRow' => $selectedRow?->load(['comments.user', 'comments' => function ($query): void {
                    $query->orderBy('created_at', 'desc');
                }]),
                'templates' => $templates,
                'columnCalculatedNames' => $cachedPayload['columnCalculatedNames'],
            ],
            'projectMoneySources' => $project->moneySources()->get(),
            'contractTypes' => $staticLookups['contractTypes'],
            'companyTypes' => $staticLookups['companyTypes'],
            'currencies' => $staticLookups['currencies'],
            'sageNotAssigned' => $sageNotAssigned,
            'recentlyCreatedSageAssignedDataComment' => $this->determineRecentlyCreatedSageAssignedDataComment(),
            'projectGroupRelevantBudgetData' => $cachedPayload['projectGroupRelevantBudgetData'],
        ];

        return $loadedProjectInformation;
    }

    /**
     * Baut den Payload-Cache eines Projekts neu auf, sofern er kalt ist.
     * Wird nach Invalidierungen asynchron aufgerufen (WarmBudgetCache-Job).
     */
    public function warmBudgetCache(int $projectId): void
    {
        $project = Project::find($projectId);
        $table = $project?->table()->first();

        if (!$project || !$table) {
            return;
        }

        // Zwischenzeitlich (z. B. durch einen Lese-Request) schon neu aufgebaut
        if ($this->budgetCacheService->getBudgetPayload($projectId) !== null) {
            return;
        }

        $this->budgetCacheService->setBudgetPayload($projectId, $this->buildBudgetPayload($project, $table));
    }

    /**
     * Baut nach einer Zell-Änderung einen schlanken Patch für das Frontend:
     * die frischen Zellen der betroffenen Zeile plus die neu berechneten
     * Summen der betroffenen Sub-/Hauptposition und die Tabellen-Randwerte.
     * Der volle Payload wird dabei einmal neu aufgebaut und direkt in den
     * Cache geschrieben - der WarmBudgetCache-Job findet danach einen warmen
     * Cache vor und beendet sich sofort.
     *
     * @return array<string, mixed>
     */
    public function buildCellUpdatePatchForRow(Project $project, int $subPositionRowId): array
    {
        $table = $project->table()->first();

        if (!$table) {
            return [];
        }

        $payload = $this->buildBudgetPayload($project, $table);
        $this->budgetCacheService->setBudgetPayload($project->id, $payload);

        /** @var Table $freshTable */
        $freshTable = $payload['table'];

        $patch = [
            'rowCells' => [],
            'subPositionSums' => [],
            'mainPositionSums' => [],
            'tableSums' => [
                'costSums' => $freshTable->getAttribute('costSums'),
                'earningSums' => $freshTable->getAttribute('earningSums'),
                'commentedCostSums' => $freshTable->getAttribute('commentedCostSums'),
                'commentedEarningSums' => $freshTable->getAttribute('commentedEarningSums'),
                'costSumDetails' => $freshTable->getAttribute('costSumDetails'),
                'earningSumDetails' => $freshTable->getAttribute('earningSumDetails'),
            ],
        ];

        foreach ($freshTable->mainPositions as $mainPosition) {
            foreach ($mainPosition->subPositions as $subPosition) {
                foreach ($subPosition->subPositionRows as $row) {
                    if ($row->id !== $subPositionRowId) {
                        continue;
                    }

                    foreach ($row->cells as $cell) {
                        $patch['rowCells'][] = [
                            'id' => $cell->id,
                            'column_id' => $cell->column_id,
                            'value' => $cell->value,
                            'verified_value' => $cell->verified_value,
                            'commented' => $cell->commented,
                            'display_value' => $cell->getAttribute('display_value'),
                            'sage_value' => $cell->getAttribute('sage_value'),
                            'current_value' => $cell->getAttribute('current_value'),
                        ];
                    }

                    $patch['subPositionSums'][$subPosition->id] = $subPosition->getAttribute('columnSums');
                    $patch['mainPositionSums'][$mainPosition->id] = [
                        'columnSums' => $mainPosition->getAttribute('columnSums'),
                        'columnVerifiedChanges' => $mainPosition->getAttribute('columnVerifiedChanges'),
                    ];

                    return $patch;
                }
            }
        }

        return $patch;
    }

    private function buildBudgetPayload(Project $project, Table $table): array
    {
        $columns = $table->columns()->get();

        // Ensure subprojects column exists for group projects
        if ($project->is_group) {
            $hasSubprojectsColumn = $columns->contains(function ($column) {
                return $column->type === 'subprojects_column_for_group';
            });

            if (!$hasSubprojectsColumn) {
                $this->columnService->createColumnInTable(
                    table: $table,
                    name: 'Unterprojekte',
                    subName: '-',
                    type: 'subprojects_column_for_group',
                    position: 100
                );
            }
        }

        // Calculate column names for difference/sum columns
        $calculateNames = $this->buildColumnCalculatedNames($columns);

        // Main eager-load query
        $table = $project->table()
            ->with([
                'columns' => function (HasMany $query): void {
                    $query->orderBy('position');
                    $query->orderByRaw('CASE WHEN type = "sage" THEN 1 ELSE 0 END');
                },
                'mainPositions' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.verified',
                'mainPositions.subPositions' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions.verified',
                'mainPositions.subPositions.subPositionRows' => function ($query) {
                    return $query->orderBy('order')->orderBy('id');
                },
                'mainPositions.subPositions.subPositionRows.cells' => function (HasMany $query): void {
                    $query
                        ->with([
                            'sageAssignedData',
                            'sageAssignedData.findChildren',
                            'sageAssignedData.comments' => function (HasMany $hasMany): HasMany {
                                return $hasMany->orderBy('created_at', 'desc');
                            },
                            'sageAssignedData.comments.user',
                            'calculations' => function ($calculations): void {
                                $calculations->orderBy('position', 'asc');
                            },
                            'comments.user',
                            'comments' => function ($query): void {
                                $query->orderBy('created_at', 'desc');
                            },
                            'linkedMoneySource'
                        ])
                        ->join('columns', 'column_sub_position_row.column_id', '=', 'columns.id')
                        ->orderByRaw('CASE WHEN type = "sage" THEN 1 ELSE 0 END')
                        ->orderBy('position')
                        ->orderBy('id')
                        ->select('column_sub_position_row.*')
                        ->withCount('comments')
                        ->withCount(['calculations' => function ($query) {
                            return $query->where('value', '!=', 0);
                        }]);
                },
                'mainPositions.subPositions.subPositionRows.cells.column',
            ])
            ->first();

        // Compute sums from loaded relations (replaces $appends)
        $this->budgetSumCalculator->computeAndAttachSums($table);

        // Enrich display values
        $this->enrichAccountManagementDisplayValues($table);

        // Project group data (optimized)
        $groupedProjectData = $project->is_group
            ? $this->computeProjectGroupData($project, $table)
            : [];

        $this->enrichProjectGroupSubprojectsColumnValues($table, $groupedProjectData);

        return [
            'table' => $table,
            'columnCalculatedNames' => $calculateNames,
            'projectGroupRelevantBudgetData' => $groupedProjectData,
        ];
    }

    private function buildColumnCalculatedNames(Collection $columns): array
    {
        $columnsById = $columns->keyBy('id');
        $calculateNames = [];

        foreach ($columns as $column) {
            $calculateName = '';
            if ($column->type === 'difference' || $column->type === 'sum') {
                $firstName = $columnsById->get($column->linked_first_column)?->subName;
                $secondName = $columnsById->get($column->linked_second_column)?->subName;
                if ($column->type === 'difference') {
                    $calculateName = $firstName . ' - ' . $secondName;
                } else {
                    $calculateName = $firstName . ' + ' . $secondName;
                }
            }
            $calculateNames[$column->id] = $calculateName;
        }

        return $calculateNames;
    }

    private function resolveSelectedSumDetail(): ?array
    {
        if (request('selectedSubPosition') && request('selectedColumn')) {
            $sumDetail = SubPositionSumDetail::firstOrCreate([
                'sub_position_id' => request('selectedSubPosition'),
                'column_id' => request('selectedColumn'),
            ]);
            $sumDetail->load(['comments.user', 'sumMoneySource.moneySource']);
            return array_merge($sumDetail->toArray(), ['class' => SubPositionSumDetail::class]);
        }

        if (request('selectedMainPosition') && request('selectedColumn')) {
            $sumDetail = MainPositionDetails::firstOrCreate([
                'main_position_id' => request('selectedMainPosition'),
                'column_id' => request('selectedColumn'),
            ]);
            $sumDetail->load(['comments.user', 'sumMoneySource.moneySource']);
            return array_merge($sumDetail->toArray(), ['class' => MainPositionDetails::class]);
        }

        if (request('selectedBudgetType') && request('selectedColumn')) {
            $sumDetail = BudgetSumDetails::firstOrCreate([
                'type' => request('selectedBudgetType'),
                'column_id' => request('selectedColumn'),
            ]);
            $sumDetail->load(['comments.user', 'sumMoneySource.moneySource']);
            return array_merge($sumDetail->toArray(), ['class' => BudgetSumDetails::class]);
        }

        return null;
    }

    private function resolveSageNotAssigned(Project $project): array
    {
        $projectsGroup = collect();
        $globalGroup = collect();

        if ($this->sageApiSettingsService->getFirst()?->enabled) {
            $user = Auth::user();
            $canViewProjectSageData = $user->can(PermissionEnum::VIEW_PROJECT_SAGE_DATA->value);
            $canViewGlobalSageData = $user->can(PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value);

            if ($canViewProjectSageData || $canViewGlobalSageData) {
                $sageNotAssigned = $this->sageNotAssignedDataService->getForFrontend($project);

                $sageNotAssigned->each(function ($item) use ($projectsGroup, $globalGroup, $project, $canViewProjectSageData, $canViewGlobalSageData): void {
                    if ($item->project_id === null && $canViewGlobalSageData) {
                        $globalGroup->push($item);
                    } elseif ($item->project_id === $project->id && $canViewProjectSageData) {
                        $projectsGroup->push($item);
                    }
                });
            }
        }

        return [
            'projectsGroup' => $projectsGroup,
            'globalGroup' => $globalGroup,
        ];
    }

    /**
     * Optimized project group data computation.
     * Bulk-loads all sub-project tables with relations instead of N+1 queries per row.
     *
     * @return array<string, array<int, MatchRelevantProjectGroupDTO>>
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function computeProjectGroupData(Project $project, Table $table): array
    {
        $groupProjects = $project->projectsOfGroup;

        if ($groupProjects->isEmpty()) {
            return [];
        }

        $groupColumns = $table->columns ?? collect();
        $firstTwoGroupColumns = $groupColumns->sortBy('position')->take(2);

        if ($firstTwoGroupColumns->count() < 2) {
            return [];
        }

        [$firstGroupColumn, $secondGroupColumn] = [$firstTwoGroupColumns->first(), $firstTwoGroupColumns->last()];

        // Build cell index for group table rows (O(1) lookups)
        $groupCellsByRowId = [];
        foreach ($table->mainPositions ?? [] as $mp) {
            foreach ($mp->subPositions ?? [] as $sp) {
                foreach ($sp->subPositionRows ?? [] as $row) {
                    $groupCellsByRowId[$row->id] = $row->cells ? $row->cells->keyBy('column_id') : collect();
                }
            }
        }

        // Bulk-load all sub-project tables with their relations
        $subProjectIds = $groupProjects->pluck('id');
        $subProjectTables = Table::whereIn('project_id', $subProjectIds)
            ->with([
                'columns',
                'mainPositions.subPositions.subPositionRows.cells' => function ($query): void {
                    $query->with('sageAssignedData');
                },
            ])
            ->get()
            ->keyBy('project_id');

        // Build cell index for sub-project rows
        $subCellsByRowId = [];
        $subProjectTableData = [];
        foreach ($subProjectTables as $projectId => $subTable) {
            $subColumns = $subTable->columns ?? collect();
            $firstTwoSubColumns = $subColumns->sortBy('position')->take(2);
            if ($firstTwoSubColumns->count() < 2) {
                continue;
            }

            $subProjectTableData[$projectId] = [
                'columns' => $subColumns,
                'firstColumn' => $firstTwoSubColumns->first(),
                'secondColumn' => $firstTwoSubColumns->last(),
                // Budgetrelevante Spalte des Unterprojekts; Fallback für Altbestand
                // ohne Flag: hinterste Wertspalte (zentral im Service).
                'relevantColumns' => collect(
                    [app(ColumnRelevanceService::class)->resolveRelevantColumn($subColumns)]
                )->filter(),
                'sageColumn' => $subColumns->firstWhere('type', 'sage'),
            ];

            foreach ($subTable->mainPositions ?? [] as $mp) {
                foreach ($mp->subPositions ?? [] as $sp) {
                    foreach ($sp->subPositionRows ?? [] as $row) {
                        $subCellsByRowId[$row->id] = $row->cells ? $row->cells->keyBy('column_id') : collect();
                    }
                }
            }
        }

        $groupedProjectData = [];
        $existingEntries = [];

        foreach ($table->mainPositions ?? [] as $groupMainPosition) {
            foreach ($groupMainPosition->subPositions ?? [] as $groupSubPosition) {
                foreach ($groupSubPosition->subPositionRows ?? [] as $groupRow) {
                    $groupRowCells = $groupCellsByRowId[$groupRow->id] ?? collect();
                    $groupFirstValue = trim((string) ($groupRowCells->get($firstGroupColumn->id)?->value ?? ''));
                    $groupSecondValue = trim((string) ($groupRowCells->get($secondGroupColumn->id)?->value ?? ''));

                    if ($groupFirstValue === '' || $groupSecondValue === '') {
                        continue;
                    }

                    foreach ($groupProjects as $subProject) {
                        $subTable = $subProjectTables->get($subProject->id);
                        if (!$subTable || !isset($subProjectTableData[$subProject->id])) {
                            continue;
                        }

                        $tableData = $subProjectTableData[$subProject->id];

                        foreach ($subTable->mainPositions ?? [] as $subMainPosition) {
                            if ($groupMainPosition->type !== $subMainPosition->type) {
                                continue;
                            }

                            foreach ($subMainPosition->subPositions ?? [] as $subPosition) {
                                foreach ($subPosition->subPositionRows ?? [] as $subRow) {
                                    $subRowCells = $subCellsByRowId[$subRow->id] ?? collect();
                                    $subFirstValue = trim((string) ($subRowCells->get($tableData['firstColumn']->id)?->value ?? ''));
                                    $subSecondValue = trim((string) ($subRowCells->get($tableData['secondColumn']->id)?->value ?? ''));

                                    if ($groupFirstValue !== $subFirstValue || $groupSecondValue !== $subSecondValue) {
                                        continue;
                                    }

                                    foreach ($tableData['relevantColumns'] as $relevantColumn) {
                                        $relevantCell = $subRowCells->get($relevantColumn->id);
                                        $relevantValue = $relevantCell?->value ?? 0;
                                        $cellId = $relevantCell?->id;

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

                                    $sageColumn = $tableData['sageColumn'];
                                    if ($sageColumn) {
                                        $sageCell = $subRowCells->get($sageColumn->id);
                                        if ($sageCell && $sageCell->relationLoaded('sageAssignedData')
                                            && $sageCell->sageAssignedData->isNotEmpty()) {
                                            $sageValue = (string) $sageCell->sageAssignedData->sum('buchungsbetrag');
                                            $sageCellId = $sageCell->id;
                                            $uniqueSageKey = $sageCellId . '-sage';

                                            if (!isset($existingEntries[$uniqueSageKey])) {
                                                $sageDtoObject = new MatchRelevantProjectGroupDTO(
                                                    $subProject->id,
                                                    $subProject->name,
                                                    $groupRow->id,
                                                    $groupRow->name,
                                                    $groupSubPosition->id,
                                                    $groupMainPosition->id,
                                                    $sageColumn->id,
                                                    $sageColumn->name,
                                                    $sageValue,
                                                    $sageCellId,
                                                    $subMainPosition->type,
                                                    $groupRow->commented,
                                                    $subFirstValue,
                                                    $subSecondValue
                                                );

                                                if ($subMainPosition->type === 'BUDGET_TYPE_EARNING') {
                                                    $groupedProjectData['BUDGET_TYPE_EARNING'][] = $sageDtoObject;
                                                } else {
                                                    $groupedProjectData['BUDGET_TYPE_COST'][] = $sageDtoObject;
                                                }

                                                $existingEntries[$uniqueSageKey] = true;
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

        return $groupedProjectData;
    }

    /**
     * Für KTO/KST (erste zwei Spalten) sollen weiterhin die Nummern in `value` gespeichert bleiben,
     * aber im Frontend der Klartext-Name angezeigt werden.
     *
     * Daher reichern wir die geladenen Zellen nur für die Ausgabe mit einem nicht-persistierten
     * Attribut `display_value` an.
     */
    private function enrichAccountManagementDisplayValues(?Table $table): void
    {
        if (!$table) {
            return;
        }

        $columns = $table->columns?->sortBy('position')->values() ?? collect();
        if ($columns->count() < 2) {
            return;
        }

        $ktoColumnId = $columns->get(0)?->id;
        $kstColumnId = $columns->get(1)?->id;

        if (!$ktoColumnId || !$kstColumnId) {
            return;
        }

        $ktoValues = [];
        $kstValues = [];

        foreach ($table->mainPositions ?? [] as $mainPosition) {
            foreach ($mainPosition->subPositions ?? [] as $subPosition) {
                foreach ($subPosition->subPositionRows ?? [] as $row) {
                    foreach ($row->cells ?? [] as $cell) {
                        $rawValue = trim((string) ($cell->value ?? ''));
                        if ($rawValue === '') {
                            continue;
                        }

                        if ((int) $cell->column_id === (int) $ktoColumnId) {
                            $ktoValues[] = $rawValue;
                        } elseif ((int) $cell->column_id === (int) $kstColumnId) {
                            $kstValues[] = $rawValue;
                        }
                    }
                }
            }
        }

        $ktoValues = array_values(array_unique($ktoValues));
        $kstValues = array_values(array_unique($kstValues));

        $accountTitlesByNumber = empty($ktoValues)
            ? collect()
            : BudgetManagementAccount::query()
                ->whereIn('account_number', $ktoValues)
                ->get(['account_number', 'title'])
                ->pluck('title', 'account_number');

        $costUnitTitlesByNumber = empty($kstValues)
            ? collect()
            : BudgetManagementCostUnit::query()
                ->whereIn('cost_unit_number', $kstValues)
                ->get(['cost_unit_number', 'title'])
                ->pluck('title', 'cost_unit_number');

        foreach ($table->mainPositions ?? [] as $mainPosition) {
            foreach ($mainPosition->subPositions ?? [] as $subPosition) {
                foreach ($subPosition->subPositionRows ?? [] as $row) {
                    foreach ($row->cells ?? [] as $cell) {
                        $rawValue = trim((string) ($cell->value ?? ''));
                        if ($rawValue === '') {
                            continue;
                        }

                        if ((int) $cell->column_id === (int) $ktoColumnId) {
                            $cell->setAttribute('display_value', $accountTitlesByNumber->get($rawValue));
                        } elseif ((int) $cell->column_id === (int) $kstColumnId) {
                            $cell->setAttribute('display_value', $costUnitTitlesByNumber->get($rawValue));
                        }
                    }
                }
            }
        }
    }

    /**
     * Projektgruppen-Funktionalität: In der Spalte "Unterprojekte" (Typ `subprojects_column_for_group`)
     * soll pro Gruppen-Zeile die Summe aller relevanten Werte aus den Unterprojekten angezeigt werden,
     * die dieselbe KTO/KST-Kombination besitzen.
     *
     * Das Frontend berechnet diese Summe grundsätzlich aus `projectGroupRelevantBudgetData`.
     * Da es in der Praxis jedoch vorkam, dass dort nicht korrekt gefiltert werden kann (Anzeige 0),
     * reichern wir zusätzlich den Zellwert der Unterprojekte-Spalte in der Response an.
     *
     * Wichtig: Wir persistieren diese Summen NICHT in der DB, sondern setzen nur den Wert am geladenen
     * Model für die Ausgabe.
     *
     * @param array<string, array<int, MatchRelevantProjectGroupDTO>> $groupedProjectData
     */
    /**
     * Nutzereingaben wie "1.234,56 €" in einen bcmath-tauglichen String überführen.
     * Nicht interpretierbare Werte werden als '0' behandelt statt bcadd() crashen zu lassen.
     */
    private function normalizeDecimalString(mixed $value): string
    {
        $raw = preg_replace('/[^0-9,.\-]/', '', trim((string) ($value ?? ''))) ?? '';

        if ($raw === '') {
            return '0';
        }

        $lastComma = strrpos($raw, ',');
        $lastDot = strrpos($raw, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Hinteres Zeichen ist der Dezimaltrenner, das andere der Tausendertrenner
            $raw = $lastComma > $lastDot
                ? str_replace(',', '.', str_replace('.', '', $raw))
                : str_replace(',', '', $raw);
        } elseif ($lastComma !== false) {
            $raw = substr_count($raw, ',') > 1
                ? str_replace(',', '', $raw)
                : str_replace(',', '.', $raw);
        } elseif ($lastDot !== false && substr_count($raw, '.') > 1) {
            $raw = str_replace('.', '', $raw);
        }

        return preg_match('/^-?\d+(\.\d+)?$/', $raw) === 1 ? $raw : '0';
    }

    private function enrichProjectGroupSubprojectsColumnValues(?Table $table, array $groupedProjectData): void
    {
        if (!$table) {
            return;
        }

        // Unterprojekte-Spalte ermitteln
        $subprojectsColumnId = $table->columns
            ?->firstWhere('type', 'subprojects_column_for_group')
            ?->id;

        if (!$subprojectsColumnId) {
            return;
        }

        $columns = $table->columns ?? collect();
        // Budgetrelevante Spalte der Gruppe; Fallback für Altbestand ohne Flag:
        // hinterste Wertspalte (zentral im Service).
        $relevantColumns = collect(
            [app(ColumnRelevanceService::class)->resolveRelevantColumn($columns)]
        )->filter();
        $sageColumn = $columns->firstWhere('type', 'sage');

        $sumByGroupRowIdAndColumn = [];

        foreach ($groupedProjectData as $typeEntries) {
            foreach ($typeEntries as $dto) {
                if (!$dto instanceof MatchRelevantProjectGroupDTO) {
                    continue;
                }

                $groupRowId = (int) $dto->groupRowId;
                $columnId = (int) $dto->relevantColumnId;
                $raw = $this->normalizeDecimalString($dto->value);

                if (!isset($sumByGroupRowIdAndColumn[$groupRowId])) {
                    $sumByGroupRowIdAndColumn[$groupRowId] = [];
                }

                $sumByGroupRowIdAndColumn[$groupRowId][$columnId] = bcadd(
                    $sumByGroupRowIdAndColumn[$groupRowId][$columnId] ?? '0',
                    $raw,
                    2
                );
            }
        }

        // Werte in die geladenen Zellen schreiben (Response-only)
        foreach ($table->mainPositions ?? [] as $mainPosition) {
            foreach ($mainPosition->subPositions ?? [] as $subPosition) {
                foreach ($subPosition->subPositionRows ?? [] as $row) {
                    $rowId = (int) $row->id;

                    foreach ($relevantColumns as $relevantColumn) {
                        $columnId = (int) $relevantColumn->id;
                        $groupCell = $row->cells?->firstWhere('column_id', $columnId);

                        if ($groupCell && trim((string) ($groupCell->value ?? '')) !== '') {
                            $groupValue = $this->normalizeDecimalString($groupCell->value);

                            if (!isset($sumByGroupRowIdAndColumn[$rowId])) {
                                $sumByGroupRowIdAndColumn[$rowId] = [];
                            }
                            $sumByGroupRowIdAndColumn[$rowId][$columnId] = $groupValue;
                        }
                    }

                    if ($sageColumn) {
                        $sageColumnId = (int) $sageColumn->id;
                        $groupSageCell = $row->cells?->firstWhere('column_id', $sageColumnId);

                        if ($groupSageCell && $groupSageCell->sageAssignedData && $groupSageCell->sageAssignedData->isNotEmpty()) {
                            $sageValue = (string) $groupSageCell->sageAssignedData->sum('buchungsbetrag');

                            if (!isset($sumByGroupRowIdAndColumn[$rowId])) {
                                $sumByGroupRowIdAndColumn[$rowId] = [];
                            }
                            $sumByGroupRowIdAndColumn[$rowId][$sageColumnId] = $sageValue;
                        }
                    }

                    if (!isset($sumByGroupRowIdAndColumn[$rowId])) {
                        continue;
                    }

                    // Alle Einträge stammen bereits ausschließlich aus budgetrelevanten
                    // bzw. Sage-Spalten (der Unterprojekte UND der Gruppe). Die Keys sind
                    // Spalten-IDs der UNTERPROJEKT-Tabellen - ein Lookup in den
                    // Gruppenspalten würde die Unterprojekt-Summen verwerfen.
                    $totalSum = '0';
                    foreach ($sumByGroupRowIdAndColumn[$rowId] as $colSum) {
                        $totalSum = bcadd($totalSum, $colSum, 2);
                    }

                    $sumForFrontend = str_replace('.', ',', $totalSum);

                    $cell = $row->cells?->firstWhere('column_id', $subprojectsColumnId);
                    if ($cell) {
                        $cell->value = $sumForFrontend;
                    } else {
                        $virtualCell = new ColumnCell([
                            'column_id' => $subprojectsColumnId,
                            'sub_position_row_id' => $row->id,
                            'value' => $sumForFrontend,
                            'commented' => false,
                        ]);
                        $row->cells?->push($virtualCell);
                    }
                }
            }
        }
    }


    private function determineRecentlyCreatedSageAssignedDataComment(
    ): SageAssignedDataComment|null {
        $recentlyCreatedSageAssignedDataComment = null;

        if ($recentlyCreatedSageAssignedDataCommentId = session('recentlyCreatedSageAssignedDataCommentId')) {
            $recentlyCreatedSageAssignedDataComment = $this->sageAssignedDataCommentService->getById(
                $recentlyCreatedSageAssignedDataCommentId
            );
        }

        if ($recentlyCreatedSageAssignedDataComment instanceof SageAssignedDataComment) {
            $recentlyCreatedSageAssignedDataComment->load('user');
        }

        return $recentlyCreatedSageAssignedDataComment;
    }
}
