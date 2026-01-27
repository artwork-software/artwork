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
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
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
        private readonly SageApiSettingsService $sageApiSettingsService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService
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
            $user = Auth::user();
            $canViewProjectSageData = $user->can(PermissionEnum::VIEW_PROJECT_SAGE_DATA->value) ||
                $user->can(PermissionEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value); // Legacy support
            $canViewGlobalSageData = $user->can(PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value) ||
                $user->can(PermissionEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value); // Legacy support

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

        $groupedProjectData = [];
        $existingEntries = [];
        if ($project->is_group) {
            // Check if the project group has the "Unterprojekte" column, and add it if it doesn't
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

                // Refresh columns after adding the new one
                $columns = $table->columns()->get();
            }

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
                            $sageColumn = $subProjectColumns->firstWhere('type', 'sage');

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

                                            // Sage-Daten aus Unterprojekten sammeln
                                            if ($sageColumn) {
                                                $sageCell = $subRow->cells()->where('column_id', $sageColumn->id)->first();
                                                if ($sageCell) {
                                                    // Sage-Daten explizit laden, falls noch nicht geladen
                                                    if (!$sageCell->relationLoaded('sageAssignedData')) {
                                                        $sageCell->load('sageAssignedData');
                                                    }

                                                    if ($sageCell->sageAssignedData && $sageCell->sageAssignedData->isNotEmpty()) {
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
                }
            }
        }

        $loadedProjectInformation['BudgetTab'] = [
            'moneySources' => MoneySource::all(),
            'budget' => [
                'table' => ($table = $project->table()
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
                                // sage cells should be at the end
                                ->join('columns', 'column_sub_position_row.column_id', '=', 'columns.id')
                                // Order of sorts is important!
                                ->orderByRaw('CASE WHEN type = "sage" THEN 1 ELSE 0 END')
                                ->orderBy('position')
                                ->orderBy('id')
                                ->select('column_sub_position_row.*')
                                ->withCount('comments')
                                ->withCount(['calculations' => function ($query) {
                                    // count if value is not 0
                                    return $query->where('value', '!=', 0);
                                }]);
                        },
                        'mainPositions.subPositions.subPositionRows.cells.column',
                    ])
                    ->first()),
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

        $this->enrichAccountManagementDisplayValues($table);
        $this->enrichProjectGroupSubprojectsColumnValues($table, $groupedProjectData);

        return $loadedProjectInformation;
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
        $relevantColumns = $columns->where('relevant_for_project_groups', true);
        $sageColumn = $columns->firstWhere('type', 'sage');

        $sumByGroupRowIdAndColumn = [];

        foreach ($groupedProjectData as $typeEntries) {
            foreach ($typeEntries as $dto) {
                if (!$dto instanceof MatchRelevantProjectGroupDTO) {
                    continue;
                }

                $groupRowId = (int) $dto->groupRowId;
                $columnId = (int) $dto->relevantColumnId;
                $raw = str_replace(',', '.', (string) ($dto->value ?? '0'));
                $raw = $raw === '' ? '0' : $raw;

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
                            $groupValue = str_replace(',', '.', (string) $groupCell->value);
                            $groupValue = $groupValue === '' ? '0' : $groupValue;

                            if (!isset($sumByGroupRowIdAndColumn[$rowId])) {
                                $sumByGroupRowIdAndColumn[$rowId] = [];
                            }
                            $sumByGroupRowIdAndColumn[$rowId][$columnId] = $groupValue;
                        }
                    }

                    // Für Sage-Spalte prüfen, ob bereits Sage-Daten in der Projektgruppe existieren
                    if ($sageColumn) {
                        $sageColumnId = (int) $sageColumn->id;
                        $groupSageCell = $row->cells?->firstWhere('column_id', $sageColumnId);

                        if ($groupSageCell && $groupSageCell->sageAssignedData && $groupSageCell->sageAssignedData->isNotEmpty()) {
                            // Wenn bereits Sage-Daten in der Projektgruppe existieren, verwende diese
                            $sageValue = (string) $groupSageCell->sageAssignedData->sum('buchungsbetrag');

                            if (!isset($sumByGroupRowIdAndColumn[$rowId])) {
                                $sumByGroupRowIdAndColumn[$rowId] = [];
                            }
                            $sumByGroupRowIdAndColumn[$rowId][$sageColumnId] = $sageValue;
                        }
                    }

                    // Summiere alle relevanten Spalten für die Unterprojekte-Spalte
                    if (!isset($sumByGroupRowIdAndColumn[$rowId])) {
                        continue;
                    }

                    $totalSum = '0';
                    foreach ($sumByGroupRowIdAndColumn[$rowId] as $colId => $colSum) {
                        // Nur relevante Spalten und Sage-Spalte berücksichtigen
                        $col = $columns->firstWhere('id', $colId);
                        if ($col && (($col->relevant_for_project_groups ?? false) || $col->type === 'sage')) {
                            $totalSum = bcadd($totalSum, $colSum, 2);
                        }
                    }

                    $sumForFrontend = str_replace('.', ',', $totalSum);

                    $cell = $row->cells?->firstWhere('column_id', $subprojectsColumnId);
                    if ($cell) {
                        $cell->value = $sumForFrontend;
                    } else {
                        // Falls die Zelle nicht existiert (z.B. frisch hinzugefügte Spalte), fügen wir sie nur
                        // für die Response hinzu.
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
