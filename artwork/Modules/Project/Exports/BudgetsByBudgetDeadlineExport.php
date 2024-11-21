<?php

namespace Artwork\Modules\Project\Exports;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageAssignedData;
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

class BudgetsByBudgetDeadlineExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly string $startBudgetDeadline,
        private readonly string $endBudgetDeadline,
    ) {
    }

    public function view(): View
    {
        return view('exports.projectBudgetsByBudgetDeadline', ['rows' => $this->getRows()]);
    }

    /**
     * @return array<string, mixed>
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
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
            $projectBudgetTable = $project->table()->with(['columns'])->first();

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
                ];

                continue;
            }

            $lastColumnId = $lastColumn->id;
            //get sums of last column which is not a sum or difference column
            $costSumOfLastColumn = $projectBudgetTable->costSums[$lastColumnId] ?? 0;
            $earningSumOfLastColumn = $projectBudgetTable->earningSums[$lastColumnId] ?? 0;

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

            /** @var ColumnCell $sageCell */
            if ($sageColumn !== null) {
                $sage = $sageRevenue = 0.00;

                foreach ($sageColumn->getRelation('cells') as $sageCell) {
                    /** @var SageAssignedData $sageAssignedData */
                    foreach ($sageCell->getAttribute('sageAssignedData') as $sageAssignedData) {
                        $value = $sageAssignedData->getAttribute('buchungsbetrag');

                        if ($value >= 0) {
                            $sage += $value;
                            continue;
                        }

                        $sageRevenue -= $value;
                    }
                }

                $row = array_merge(
                    $row,
                    [
                        'sage' => $sage,
                        'sage_revenue' => $sageRevenue,
                        'sage_result' => ($sageRevenue - $sage),
                    ]
                );
            }

            $rows[] = $row;
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
