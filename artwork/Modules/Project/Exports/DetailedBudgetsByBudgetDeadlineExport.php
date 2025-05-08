<?php

namespace Artwork\Modules\Project\Exports;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
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
                ->with([
                    'table.columns',
                    'table.mainPositions.subPositions.subPositionRows.cells.column',
                    'table.mainPositions.subPositions.subPositionRows.cells.sageAssignedData',
                ])
                ->orderBy('budget_deadline')
                ->get() as $project
        ) {


            /** @var Table $projectBudgetTable */
            $projectBudgetTable = $project->getRelation('table');
            $tableColumns = $projectBudgetTable->getRelation('columns');

            $sageColumn = $tableColumns->first(
                function (Column $column) {
                    return $column->getAttribute('type') === 'sage';
                }
            )?->load('cells.sageAssignedData');

            $lastColumn = $tableColumns->filter(fn($column) => $column->getAttribute('type') === "empty")->last();

            $premiere = Carbon::createFromFormat(
                'Y-m-d',
                $project->getAttribute('budget_deadline')
            )->format('d.m.Y');

            if ($lastColumn === null) {
                $noDataAvailable = 'Keine Daten vorhanden';
                $rows[] = [
                    'premiere' => $premiere,
                    'project_name' => $project->getAttribute('name'),
                    'artist_or_group' => $noDataAvailable,
                    'project_state' => ProjectState::query()
                        ->where('id', '=', $project->getAttribute('state'))
                        ->first('name')?->getAttribute('name'),
                    'cost_center' => $project->getAttribute('costCenter')?->getAttribute('name'),
                    'kst' => $noDataAvailable,
                    'real_account' => $noDataAvailable,
                    'forecast_costs' => $noDataAvailable,
                    'forecast_earnings' => $noDataAvailable,
                    'forecast_outcome' => $noDataAvailable,
                ];

                if ($sageColumn !== null) {
                    $rows['sage'] = $noDataAvailable;
                    $rows['sage_revenue'] = $noDataAvailable;
                    $rows['sage_result'] = $noDataAvailable;
                }

                $rows['source'] = $noDataAvailable;
                continue;
            }

            $rows = array_merge(
                $rows,
                $this->getProjectRows(
                    $premiere,
                    $projectBudgetTable,
                    $project,
                    $sageColumn,
                )
            );
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function getProjectRows(
        string $premiere,
        Table $projectBudgetTable,
        Project $project,
        ?Column $sageColumn,
    ): array {
        $tableColumns = $projectBudgetTable->getRelation('columns');
        /** @var Column $kst */
        $kstColumnId = $tableColumns->first(
            fn (Column $column) => $column->getAttribute('name') === 'KST'
        )->getAttribute('id');
        /** @var Column $kto */
        $ktoColumnId = $tableColumns->first(
            fn (Column $column) => $column->getAttribute('name') === 'KTO'
        )->getAttribute('id');

        $rows = [];
        foreach ($projectBudgetTable->getAttribute('mainPositions') as $mainPosition) {
            [
                $isCostsMainPosition,
                $subPositionRows
            ] = $this->getIsCostMainPositionAndSubPositionRows($mainPosition);

            foreach ($subPositionRows as $subPositionRow) {
                $lastColumnNotSageId = $tableColumns->last(
                    function (Column $column) {
                        return $column->getAttribute('type') !== 'sage';
                    }
                )->getAttribute('id');

                $forecastCellValue = (float) $subPositionRow->getAttribute('cells')->first(
                    fn(ColumnCell $columnCell) => (
                        $columnCell->getAttribute('column_id') === $lastColumnNotSageId
                    )
                )->getAttribute('value');

                //project row
                $row = [
                    'premiere' => $premiere,
                    'project_name' => $project->name,
                    'artist_or_group' => 'Noch nicht angegeben',
                    'project_state' => ProjectState::query()
                        ->where('id', '=', $project->state)->first('name')?->name,
                    'cost_center' => $project->costCenter?->getAttribute('name'),
                    'kst' => $subPositionRow->getAttribute('cells')->first(
                        fn (ColumnCell $columnCell) => (
                            $columnCell->getAttribute('column_id') === $kstColumnId
                        )
                    )->getAttribute('value'),
                    'real_account' => $subPositionRow->getAttribute('cells')->first(
                        fn (ColumnCell $columnCell) => (
                            $columnCell->getAttribute('column_id') === $ktoColumnId
                        )
                    )->getAttribute('value'),
                    'forecast_costs' => $isCostsMainPosition ? $forecastCellValue : 0,
                    'forecast_earnings' => !$isCostsMainPosition ? $forecastCellValue : 0,
                    'forecast_outcome' => $isCostsMainPosition ?
                        (0 - $forecastCellValue) :
                        $forecastCellValue,
                    'sage' => 0,
                    'sage_revenue' => 0,
                    'sage_result' => 0,
                    'source' => Carbon::now()->format('Y')
                ];

                $rows[] = $row;

                if (!$sageColumn) {
                    continue;
                }

                foreach (
                    $subPositionRow
                        ->getAttribute('cells')
                        ->first(
                            fn(ColumnCell $columnCell) =>
                                $columnCell->getAttribute('column_id') === $sageColumn->getAttribute('id')
                        )->getAttribute('sageAssignedData') as $sageAssignedData
                ) {
                    $sageValue = (float) $sageAssignedData->getAttribute('buchungsbetrag');

                    //sage row
                    $rows[] = [
                        'premiere' => $premiere,
                        'project_name' => $project->name,
                        'artist_or_group' => 'Noch nicht angegeben',
                        'project_state' => ProjectState::query()
                            ->where('id', '=', $project->state)->first('name')?->name,
                        'cost_center' => $project->costCenter?->getAttribute('name'),
                        'kst' => $subPositionRow->getAttribute('cells')->first(
                            fn (ColumnCell $columnCell) => (
                                $columnCell->getAttribute('column_id') === $kstColumnId
                            )
                        )->getAttribute('value'),
                        'real_account' => $subPositionRow->getAttribute('cells')->first(
                            fn (ColumnCell $columnCell) => (
                                $columnCell->getAttribute('column_id') === $ktoColumnId
                            )
                        )->getAttribute('value'),
                        'forecast_costs' => 0,
                        'forecast_earnings' => 0,
                        'forecast_outcome' => 0,
                        'sage' => $isCostsMainPosition ? $sageValue : 0,
                        'sage_revenue' => !$isCostsMainPosition ? $sageValue : 0,
                        'sage_result' => $isCostsMainPosition ?
                            (0 - $sageValue) :
                            $sageValue,
                        'source' => 'Sage Abgleich'
                    ];
                }
            }
        }


        return $rows;
    }

    /**
     * @param MainPosition $mainPosition
     * @return array<boolean, array<int, SubPositionRow>>
     */
    private function getIsCostMainPositionAndSubPositionRows(MainPosition $mainPosition): array
    {
        $isCostsMainPosition = $mainPosition->getAttribute('type') === BudgetTypeEnum::BUDGET_TYPE_COST->value;
        $subPositionRows = [];

        foreach ($mainPosition->getAttribute('subPositions') as $subPosition) {
            $subPositionRows = array_merge($subPositionRows, $subPosition->getAttribute('subPositionRows')->all());
        }

        return [$isCostsMainPosition, $subPositionRows];
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
