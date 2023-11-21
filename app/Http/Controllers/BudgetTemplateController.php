<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Artwork\Modules\Budget\Models\CellCalculations;
use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetTemplateController extends Controller
{
    protected ?array $columns = null;

    public function __construct()
    {

    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
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

        return Inertia::render('BudgetTemplates/BudgetTemplateManagement', [
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
                'selectedCell' => $selectedCell?->load(['calculations', 'comments.user', 'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }]),
                'selectedRow' => $selectedRow?->load(['comments.user', 'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }]),
                'templates' => $templates,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Table $table, Request $request)
    {
        $oldTable = $table;
        $this->createTemplate($request->template_name, $oldTable);
    }

    private function createTemplate($name, $oldTable, $isTemplate = true, $projectId = null)
    {

        //dd($oldTable);

        $newTable = Table::create([
            'name' => $name,
            'is_template' => $isTemplate,
            'project_id' => $projectId
        ]);
        $oldTable->columns->map(function (Column $column) use ($newTable) {
            $replicated_column = $column->replicate()->fill(['table_id' => $newTable->id]);
            $replicated_column->save();
            $this->columns[$column->id] = $replicated_column->id;
            $replicated_column->update([
                'linked_first_column' => $column->linked_first_column !== null ? $this->columns[$column->linked_first_column] : null,
                'linked_second_column' => $column->linked_second_column !== null ? $this->columns[$column->linked_second_column] : null
            ]);
        });

        $oldTable->mainPositions->map(function (MainPosition $mainPosition) use ($newTable) {
            $replicated_mainPosition = $mainPosition->replicate()->fill(['table_id' => $newTable->id]);
            $replicated_mainPosition->save();
            $mainPosition->subPositions->map(function (SubPosition $subPosition) use ($replicated_mainPosition) {
                $replicated_subPosition = $subPosition->replicate()->fill(['main_position_id' => $replicated_mainPosition->id]);
                $replicated_subPosition->save();
                $subPosition->subPositionRows->map(function (SubPositionRow $subPositionRow) use ($replicated_subPosition) {
                    $replicated_subPositionRow = $subPositionRow->replicate()->fill(['sub_position_id' => $replicated_subPosition->id]);
                    $replicated_subPositionRow->save();
                    $subPositionRow->cells->map(function (ColumnCell $columnCell) use ($replicated_subPositionRow) {
                        $replicated_columnCell = $columnCell->replicate()->fill(['sub_position_row_id' => $replicated_subPositionRow->id]);
                        $replicated_columnCell->linked_money_source_id = null;
                        $replicated_columnCell->linked_type = null;
                        $replicated_columnCell->column_id = $this->columns[$columnCell->column_id];
                        $replicated_columnCell->save();
                        $columnCell->comments->map(function (CellComment $cellComment) use ($replicated_columnCell) {
                            $replicated_comment = $cellComment->replicate()->fill(['column_cell_id' => $replicated_columnCell->id]);
                            $replicated_comment->save();
                        });
                        $columnCell->calculations->map(function (CellCalculations $cellCalculations) use ($replicated_columnCell) {
                            $replicated_cellCalculation = $cellCalculations->replicate()->fill(['cell_id' => $replicated_columnCell->id]);
                            $replicated_cellCalculation->save();
                        });
                    });
                    $subPositionRow->comments->map(function (RowComment $rowComment) use ($replicated_subPositionRow) {
                        $replicated_rowComment = $rowComment->replicate()->fill(['sub_position_row_id' => $replicated_subPositionRow->id]);
                        $replicated_rowComment->save();
                    });
                });
            });
        });
    }

    public function useTemplate(Table $table, Request $request)
    {
        $project = Project::find($request->project_id);

        $this->deleteOldTable($project);

        $this->createTemplate($table->name, $table, false, $project->id);

        return back()->with('success');
    }

    public function useTemplateFromProject(Request $request)
    {
        if ($request->template_project_id !== $request->project_id) {
        $templateProject = Project::find($request->template_project_id);
        $project = Project::find($request->project_id);

            $this->deleteOldTable($project);

            $this->createTemplate($templateProject->name . ' Budgettabelle', $templateProject->table()->first(), false, $project->id);
        }
    }

    public function deleteOldTable(Project $project)
    {
        $tableToDelete = $project->table()->first();

        // delete old budget table
        $tableToDelete->columns()->delete();
        $mainPositions = $tableToDelete->mainPositions()->get();
        foreach ($mainPositions as $mainPosition) {
            $subPositions = $mainPosition->subPositions()->get();
            foreach ($subPositions as $subPosition) {
                $subPositionRows = $subPosition->subPositionRows()->get();
                foreach ($subPositionRows as $subPositionRow) {
                    $cells = $subPositionRow->cells()->get();
                    foreach ($cells as $cell) {
                        $cell->comments()->delete();
                        $cell->calculations()->delete();
                        $cell->delete();
                    }
                    $subPositionRow->comments()->delete();
                    $subPositionRow->delete();
                }
                $subPosition->delete();
            }
            $mainPosition->delete();
        }

        $tableToDelete->delete();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
