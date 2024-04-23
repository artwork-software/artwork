<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetSumDetailsService;
use Artwork\Modules\Budget\Services\CellCalculationService;
use Artwork\Modules\Budget\Services\CellCommentService;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\MainPositionDetailsService;
use Artwork\Modules\Budget\Services\MainPositionService;
use Artwork\Modules\Budget\Services\MainPositionVerifiedService;
use Artwork\Modules\Budget\Services\RowCommentService;
use Artwork\Modules\Budget\Services\SageAssignedDataService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Budget\Services\SubPositionRowService;
use Artwork\Modules\Budget\Services\SubPositionService;
use Artwork\Modules\Budget\Services\SubPositionSumDetailService;
use Artwork\Modules\Budget\Services\SubPositionVerifiedService;
use Artwork\Modules\Budget\Services\SumCommentService;
use Artwork\Modules\Budget\Services\SumMoneySourceService;
use Artwork\Modules\Budget\Services\TableService;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BudgetTemplateController extends Controller
{
    protected ?array $columns = null;

    public function __construct(
        private readonly TableService $tableService
    ) {
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index(): Response
    {
        $selectedCell = request('selectedCell')
            ? ColumnCell::find(request('selectedCell'))
            : null;

        $selectedRow = request('selectedRow')
            ? SubPositionRow::find(request('selectedRow'))
            : null;

        $templates = null;


        if (request('useTemplates')) {
            $templates = Table::where('is_template', true)->get();
        }

        return Inertia::render('BudgetSettingsTemplates/Index', [
            'budget' => [
                'table' => Table::where('is_template', true)
                    ->with([
                        'columns',
                        'mainPositions',
                        'mainPositions.verified',
                        'mainPositions.subPositions' => function ($query) {
                            return $query->orderBy('position');
                        },
                        'mainPositions.subPositions.verified',
                        'mainPositions.subPositions.subPositionRows' => function ($query) {
                            return $query->orderBy('position');
                        }, 'mainPositions.subPositions.subPositionRows.cells.column'
                    ])
                    ->get(),
                'selectedCell' => $selectedCell?->load(
                    [
                        'calculations',
                        'comments.user',
                        'comments' => function ($query): void {
                            $query->orderBy('created_at', 'desc');
                        }
                    ]
                ),
                'selectedRow' => $selectedRow?->load(['comments.user', 'comments' => function ($query): void {
                    $query->orderBy('created_at', 'desc');
                }]),
                'templates' => $templates,
            ],
        ]);
    }

    public function store(Table $table, Request $request): RedirectResponse
    {
        $this->createTemplate($request->template_name, $table);
        return Redirect::back();
    }

    private function createTemplate($name, $oldTable, $isTemplate = true, $projectId = null): void
    {
        $newTable = Table::create([
            'name' => $name,
            'is_template' => $isTemplate,
            'project_id' => $projectId
        ]);
        $oldTable->columns->map(function (Column $column) use ($newTable): void {
            if ($column->type === 'sage') {
                return;
            }
            $replicated_column = $column->replicate()->fill(['table_id' => $newTable->id]);
            $replicated_column->save();
            $this->columns[$column->id] = $replicated_column->id;
            $replicated_column->update([
                'linked_first_column' => $column->linked_first_column !== null ?
                    $this->columns[$column->linked_first_column] :
                    null,
                'linked_second_column' => $column->linked_second_column !== null ?
                    $this->columns[$column->linked_second_column] :
                    null
            ]);
        });

        $oldTable->mainPositions->map(function (MainPosition $mainPosition) use ($newTable): void {
            $replicated_mainPosition = $mainPosition->replicate()->fill(['table_id' => $newTable->id]);
            $replicated_mainPosition->save();
            $mainPosition->subPositions->map(function (SubPosition $subPosition) use ($replicated_mainPosition): void {
                $replicated_subPosition = $subPosition->replicate()->fill(
                    ['main_position_id' => $replicated_mainPosition->id]
                );
                $replicated_subPosition->save();
                $subPosition->subPositionRows->map(
                    function (SubPositionRow $subPositionRow) use ($replicated_subPosition): void {
                        $replicated_subPositionRow = $subPositionRow->replicate()->fill(
                            ['sub_position_id' => $replicated_subPosition->id]
                        );
                        $replicated_subPositionRow->save();
                        $subPositionRow->cells->map(
                            function (ColumnCell $columnCell) use ($replicated_subPositionRow): void {
                                if ($columnCell->column->type === 'sage') {
                                    return;
                                }
                                $replicated_columnCell = $columnCell->replicate()->fill(
                                    ['sub_position_row_id' => $replicated_subPositionRow->id]
                                );
                                $replicated_columnCell->linked_money_source_id = null;
                                $replicated_columnCell->linked_type = null;
                                $replicated_columnCell->column_id = $this->columns[$columnCell->column_id];
                                $replicated_columnCell->save();
                                $columnCell->comments->map(
                                    function (CellComment $cellComment) use ($replicated_columnCell): void {
                                        $replicated_comment = $cellComment->replicate()->fill(
                                            ['column_cell_id' => $replicated_columnCell->id]
                                        );
                                        $replicated_comment->save();
                                    }
                                );
                                $columnCell->calculations->map(
                                    function (CellCalculation $cellCalculations) use ($replicated_columnCell): void {
                                        $replicated_cellCalculation = $cellCalculations->replicate()->fill(
                                            ['cell_id' => $replicated_columnCell->id]
                                        );
                                        $replicated_cellCalculation->save();
                                    }
                                );
                            }
                        );
                        $subPositionRow->comments->map(
                            function (RowComment $rowComment) use ($replicated_subPositionRow): void {
                                $replicated_rowComment = $rowComment->replicate()->fill(
                                    ['sub_position_row_id' => $replicated_subPositionRow->id]
                                );
                                $replicated_rowComment->save();
                            }
                        );
                    }
                );
            });
        });
    }

    public function useTemplate(
        Table $table,
        Request $request,
        MainPositionService $mainPositionService,
        ColumnService $columnService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService,
        BudgetSumDetailsService $budgetSumDetailsService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): RedirectResponse {
        $project = Project::find($request->project_id);

        $this->deleteOldTable(
            $project,
            $mainPositionService,
            $columnService,
            $sumCommentService,
            $sumMoneySourceService,
            $subPositionVerifiedService,
            $subPositionSumDetailService,
            $subPositionRowService,
            $rowCommentService,
            $columnCellService,
            $mainPositionVerifiedService,
            $mainPositionDetailsService,
            $subPositionService,
            $budgetSumDetailsService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );

        $this->createTemplate($table->name, $table, false, $project->id);

        return Redirect::back();
    }

    public function useTemplateFromProject(
        Request $request,
        MainPositionService $mainPositionService,
        ColumnService $columnService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService,
        BudgetSumDetailsService $budgetSumDetailsService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): void {
        if ($request->template_project_id !== $request->project_id) {
            $templateProject = Project::find($request->template_project_id);
            $project = Project::find($request->project_id);

            $this->deleteOldTable(
                $project,
                $mainPositionService,
                $columnService,
                $sumCommentService,
                $sumMoneySourceService,
                $subPositionVerifiedService,
                $subPositionSumDetailService,
                $subPositionRowService,
                $rowCommentService,
                $columnCellService,
                $mainPositionVerifiedService,
                $mainPositionDetailsService,
                $subPositionService,
                $budgetSumDetailsService,
                $cellCommentService,
                $cellCalculationService,
                $sageNotAssignedDataService,
                $sageAssignedDataService
            );

            $this->createTemplate(
                $templateProject->name . ' Budgettabelle',
                $templateProject->table()->first(),
                false,
                $project->id
            );
        }
    }

    public function deleteOldTable(
        Project $project,
        MainPositionService $mainPositionService,
        ColumnService $columnService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService,
        BudgetSumDetailsService $budgetSumDetailsService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): void {
        /** @var Table $tableToDelete */
        $tableToDelete = $project->table()->first();

        $this->tableService->forceDelete(
            $tableToDelete,
            $mainPositionService,
            $columnService,
            $sumCommentService,
            $sumMoneySourceService,
            $subPositionVerifiedService,
            $subPositionSumDetailService,
            $subPositionRowService,
            $rowCommentService,
            $columnCellService,
            $mainPositionVerifiedService,
            $mainPositionDetailsService,
            $subPositionService,
            $budgetSumDetailsService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );
    }
}
