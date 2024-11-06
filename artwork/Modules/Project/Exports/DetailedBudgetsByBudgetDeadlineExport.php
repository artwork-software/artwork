<?php

namespace Artwork\Modules\Project\Exports;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetailedBudgetsByBudgetDeadlineExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly string $startBudgetDeadline,
        private readonly string $endBudgetDeadline,
    ) {
    }

    public function view(): View
    {
        return view('exports.detailedProjectBudgetsByBudgetDeadline', ['rows' => $this->getRows()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getRows(): array
    {
        $rows = [];

        foreach (
            Project::query()
                ->whereBetween('budget_deadline', [$this->startBudgetDeadline, $this->endBudgetDeadline])
                ->orderBy('budget_deadline')
                ->get() as $project
        ) {
            /** @var Table $projectBudgetTable */
            $projectBudgetTable = $project->table()->with(
                [
                    'columns',
                    'mainPositions.subPositions.subPositionRows.cells.column',
                    'mainPositions.subPositions.subPositionRows.cells.sageAssignedData',
                ]
            )->first();

            $sageColumn = $projectBudgetTable->getAttribute('columns')->first(
                function (Column $column) {
                    return $column->getAttribute('type') === 'sage';
                }
            )?->load('cells.sageAssignedData');

            $lastColumn = $projectBudgetTable->columns->filter(fn($column) => $column->type === "empty")->last();
            if ($lastColumn === null) {
                $rows[] = [
                    'premiere' => Carbon::createFromFormat('Y-m-d', $project->budget_deadline)
                        ->format('d.m.Y'),
                    'project_name' => $project->getAttribute('name'),
                    'artist_or_group' => 'Noch nicht angegeben',
                    'cost_center' => $project->costCenter?->getAttribute('name'),
                    'project_state' => ProjectState::query()
                        ->where('id', '=', $project->state)->first('name')?->name,
                    'forecast_costs' => 'Keine Daten vorhanden',
                    'forecast_earnings' => 'Keine Daten vorhanden',
                    'forecast_outcome' => 'Keine Daten vorhanden',
                    'sage' => 'Keine Daten vorhanden',
                    'sage_revenue' => 'Keine Daten vorhanden',
                    'sage_result' => 'Keine Daten vorhanden',
                    'source' => 'Keine Daten vorhanden',
                ];

                continue;
            }

            //aggregated project row (with forecasts, without sage values)
            $lastColumnId = $lastColumn->id;
            //get sums of last column which is not a sum or difference column
            $costSumOfLastColumn = $projectBudgetTable->costSums[$lastColumnId] ?? 0;
            $earningSumOfLastColumn = $projectBudgetTable->earningSums[$lastColumnId] ?? 0;

            $premiere = Carbon::createFromFormat('Y-m-d', $project->budget_deadline)->format('d.m.Y');
            $row = [
                'premiere' => Carbon::createFromFormat('Y-m-d', $project->budget_deadline)
                    ->format('d.m.Y'),
                'project_name' => $project->name,
                'artist_or_group' => 'Noch nicht angegeben',
                'cost_center' => $project->costCenter?->getAttribute('name'),
                'project_state' => ProjectState::query()
                    ->where('id', '=', $project->state)->first('name')?->name,
                'forecast_costs' => $costSumOfLastColumn,
                'forecast_earnings' => $earningSumOfLastColumn,
                'forecast_outcome' => ($earningSumOfLastColumn - $costSumOfLastColumn)
            ];

            $row = array_merge(
                $row,
                [
                    'sage' => 0,
                    'sage_revenue' => 0,
                    'sage_result' => 0,
                    'source' => explode('.', $premiere)[2]
                ]
            );

            $rows[] = $row;
            //detailed row by budget table subPositionRows (with sage values, without forecasts)
            foreach ($projectBudgetTable->getAttribute('mainPositions') as $mainPosition) {
                foreach ($mainPosition->getAttribute('subPositions') as $subPosition) {
                    foreach ($subPosition->getAttribute('subPositionRows') as $subPositionRow) {
                        $row = [
                            'premiere' => $premiere,
                            'project_name' => $project->name,
                            'artist_or_group' => 'Noch nicht angegeben',
                            'cost_center' => $project->costCenter?->getAttribute('name'),
                            'project_state' => ProjectState::query()
                                ->where('id', '=', $project->state)->first('name')?->name,
                            'forecast_costs' => 0,
                            'forecast_earnings' => 0,
                            'forecast_outcome' => 0
                        ];

                        if ($sageColumn === null) {
                            $row = array_merge(
                                $row,
                                [
                                    'sage' => 0,
                                    'sage_revenue' => 0,
                                    'sage_result' => 0,
                                    'source' => 'Keine Sage-Daten vorhanden'
                                ]
                            );

                            $rows[] = $row;
                            continue;
                        }

                        /** @var ColumnCell $sageColumnCells */
                        $sageColumnCell = $subPositionRow
                            ->getAttribute('cells')
                            ->first(
                                fn(ColumnCell $cell) => (
                                    $cell->getAttribute('column_id') === $sageColumn->getAttribute('id')
                                )
                            );

                        $sum = $sageColumnCell->getAttribute('sageAssignedData')->sum('buchungsbetrag');

                        $row = array_merge(
                            $row,
                            [
                                'sage' => max($sum, 0),
                                'sage_revenue' => 0,
                                'sage_result' => min($sum, 0),
                                'source' => $sageColumn->getAttribute('name')
                            ]
                        );

                        $rows[] = $row;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @param Worksheet $sheet
     * @return array<int, array<string, array<string, mixed>>>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
    public function styles(Worksheet $sheet): array
    {
        return [
            //first row bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}
