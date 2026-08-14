<?php

namespace Artwork\Modules\Project\Exports;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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
        private readonly bool $accountManagementGlobal = false,
        private readonly ?Collection $budgetColumnSettings = null,
        private readonly ?array $desiredColumns = null,
    ) {
    }

    /**
     * @return array<int, string> alle wählbaren Spalten-Keys (inkl. der nur bei
     * globaler Kontoverwaltung sichtbaren Namensspalten)
     */
    public static function availableColumnKeys(): array
    {
        return [
            'premiere',
            'project_name',
            'artist_or_group',
            'project_state',
            'cost_center',
            'kst',
            'kst_name',
            'real_account',
            'kto_name',
            'position',
            'forecast_costs',
            'forecast_earnings',
            'forecast_outcome',
            'sage',
            'sage_revenue',
            'sage_result',
            'source',
        ];
    }

    public function view(): View
    {
        $columnNames = [];
        if ($this->accountManagementGlobal && $this->budgetColumnSettings !== null) {
            foreach ($this->budgetColumnSettings as $setting) {
                $columnNames[$setting->column_position] = $setting->column_name;
            }
        }

        return view('exports.detailedProjectBudgetsByBudgetDeadline', [
            'columns' => $this->getColumns($columnNames),
            'rows' => $this->getRows(),
        ]);
    }

    /**
     * @param array<int, string> $columnNames
     * @return array<string, string> Spalten-Key => Excel-Überschrift
     */
    private function getColumns(array $columnNames): array
    {
        $columns = [
            'premiere' => 'Premiere',
            'project_name' => 'Projektname Originalsprache',
            'artist_or_group' => 'Künstler*innen',
            'project_state' => 'Projektstatus',
            'cost_center' => 'KTR',
            'kst' => $columnNames[1] ?? 'Kst',
        ];

        if ($this->accountManagementGlobal) {
            $columns['kst_name'] = ($columnNames[1] ?? 'Kst') . ' Name';
        }

        $columns['real_account'] = $columnNames[0] ?? 'Sachkonto';

        if ($this->accountManagementGlobal) {
            $columns['kto_name'] = ($columnNames[0] ?? 'Sachkonto') . ' Name';
            $columns['position'] = $columnNames[2] ?? 'Position';
        }

        $columns += [
            'forecast_costs' => 'Vorschau Kosten',
            'forecast_earnings' => 'Vorschau Erlöse',
            'forecast_outcome' => 'Vorschau Resultat',
            'sage' => 'Ist - Sage',
            'sage_revenue' => 'Ist Erlöse',
            'sage_result' => 'Ist Resultat',
            'source' => 'Quelle (Name der Spalte im Budget)',
        ];

        if ($this->desiredColumns !== null) {
            $columns = array_filter(
                $columns,
                fn(string $key) => in_array($key, $this->desiredColumns, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        return $columns;
    }

    /**
     * @return array<string, mixed>
     */
    private function getRows(): array
    {
        $rows = [];

        $accountLookup = collect();
        $costUnitLookup = collect();
        if ($this->accountManagementGlobal) {
            $accountLookup = BudgetManagementAccount::all()->keyBy('account_number');
            $costUnitLookup = BudgetManagementCostUnit::all()->keyBy('cost_unit_number');
        }

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

            $lastColumn = app(ColumnRelevanceService::class)->resolveRelevantColumn($tableColumns);

            $premiere = Carbon::createFromFormat(
                'Y-m-d',
                $project->getAttribute('budget_deadline')
            )->format('d.m.Y');

            if ($lastColumn === null) {
                $noDataAvailable = 'Keine Daten vorhanden';
                $row = [
                    'premiere' => $premiere,
                    'project_name' => $project->getAttribute('name'),
                    'artist_or_group' => '',
                    'project_state' => ProjectState::query()
                        ->where('id', '=', $project->getAttribute('state'))
                        ->first('name')?->getAttribute('name'),
                    'cost_center' => $project->getAttribute('costCenter')?->getAttribute('name'),
                    'kst' => $noDataAvailable,
                    'real_account' => $noDataAvailable,
                    'kto_name' => $noDataAvailable,
                    'kst_name' => $noDataAvailable,
                    'position' => $noDataAvailable,
                    'forecast_costs' => $noDataAvailable,
                    'forecast_earnings' => $noDataAvailable,
                    'forecast_outcome' => $noDataAvailable,
                    'source' => $noDataAvailable,
                ];

                if ($sageColumn !== null) {
                    $row['sage'] = $noDataAvailable;
                    $row['sage_revenue'] = $noDataAvailable;
                    $row['sage_result'] = $noDataAvailable;
                }

                $rows[] = $row;
                continue;
            }

            $rows = array_merge(
                $rows,
                $this->getProjectRows(
                    $premiere,
                    $projectBudgetTable,
                    $project,
                    $sageColumn,
                    $accountLookup,
                    $costUnitLookup,
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
        Collection $accountLookup,
        Collection $costUnitLookup,
    ): array {
        $tableColumns = $projectBudgetTable->getRelation('columns');

        $sortedColumns = $tableColumns->sortBy('position')->values();
        $ktoColumnId = $sortedColumns->get(0)?->getAttribute('id');
        $kstColumnId = $sortedColumns->get(1)?->getAttribute('id');
        $positionColumnId = $sortedColumns->get(2)?->getAttribute('id');

        // Die budgetrelevante Spalte liefert die Vorschau-Werte - identisch zur
        // aggregierten Export-Variante. Fallback für Altbestand ohne Flag:
        // hinterste Wertspalte (zentral im Service).
        $forecastColumnId = app(ColumnRelevanceService::class)
            ->resolveRelevantColumn($tableColumns)
            ?->getAttribute('id');

        $rows = [];
        foreach ($projectBudgetTable->getAttribute('mainPositions') as $mainPosition) {
            [
                $isCostsMainPosition,
                $subPositionRows
            ] = $this->getIsCostMainPositionAndSubPositionRows($mainPosition);

            foreach ($subPositionRows as $subPositionRow) {
                $forecastCellValue = (float) $subPositionRow->getAttribute('cells')->first(
                    fn(ColumnCell $columnCell) => (
                        $columnCell->getAttribute('column_id') === $forecastColumnId
                    )
                )?->getAttribute('value');

                $kstValue = $kstColumnId ? $subPositionRow->getAttribute('cells')->first(
                    fn (ColumnCell $columnCell) => (
                        $columnCell->getAttribute('column_id') === $kstColumnId
                    )
                )?->getAttribute('value') : '';

                $ktoValue = $ktoColumnId ? $subPositionRow->getAttribute('cells')->first(
                    fn (ColumnCell $columnCell) => (
                        $columnCell->getAttribute('column_id') === $ktoColumnId
                    )
                )?->getAttribute('value') : '';

                $positionValue = $positionColumnId ? $subPositionRow->getAttribute('cells')->first(
                    fn (ColumnCell $columnCell) => (
                        $columnCell->getAttribute('column_id') === $positionColumnId
                    )
                )?->getAttribute('value') : '';

                //project row
                $row = [
                    'premiere' => $premiere,
                    'project_name' => $project->name,
                    'artist_or_group' => $project->getAttribute('artists') ?? '',
                    'project_state' => ProjectState::query()
                        ->where('id', '=', $project->state)->first('name')?->name,
                    'cost_center' => $project->costCenter?->getAttribute('name'),
                    'kst' => $kstValue,
                    'real_account' => $ktoValue,
                    'kto_name' => $this->accountManagementGlobal
                        ? ($accountLookup[$ktoValue]?->title ?? '')
                        : '',
                    'kst_name' => $this->accountManagementGlobal
                        ? ($costUnitLookup[$kstValue]?->title ?? '')
                        : '',
                    'position' => $positionValue ?? '',
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
                        'artist_or_group' => $project->getAttribute('artists') ?? '',
                        'project_state' => ProjectState::query()
                            ->where('id', '=', $project->state)->first('name')?->name,
                        'cost_center' => $project->costCenter?->getAttribute('name'),
                        'kst' => $kstValue,
                        'real_account' => $ktoValue,
                        'kto_name' => $this->accountManagementGlobal
                            ? ($accountLookup[$ktoValue]?->title ?? '')
                            : '',
                        'kst_name' => $this->accountManagementGlobal
                            ? ($costUnitLookup[$kstValue]?->title ?? '')
                            : '',
                        'position' => $positionValue ?? '',
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
