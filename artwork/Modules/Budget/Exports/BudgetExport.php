<?php

namespace Artwork\Modules\Budget\Exports;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(private readonly Project $project)
    {
    }

    public function view(): View
    {
        return view('exports.projectBudget', ['data' => $this->getData()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        /** @var Table $budgetTable */
        $budgetTable = $this->project->table()
            ->with([
                'columns' => function (HasMany $query): void {
                    $query->orderBy('position');
                    $query->orderByRaw('CASE WHEN type = "sage" THEN 1 ELSE 0 END');
                },
                'mainPositions' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions.subPositionRows' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions.subPositionRows.cells' => function (HasMany $query): void {
                    $query
                        ->join('columns', 'column_sub_position_row.column_id', '=', 'columns.id')
                        ->orderBy('position')
                        ->orderByRaw('CASE WHEN type = "sage" THEN 1 ELSE 0 END')
                        ->with('sageAssignedData')
                        ->select('column_sub_position_row.*');
                },
            ])
            ->first();

        $columnsByPosition = $budgetTable?->columns?->sortBy('position')->values() ?? collect();
        // Die ersten drei Spalten (KTO, KST, Beschreibung) sind Textspalten — nie als (float) ausgeben.
        $textColumnIds = $columnsByPosition->take(3)->pluck('id')->map(fn($id) => (int) $id)->all();

        return [
            'budgetTable' => $budgetTable,
            'textColumnIds' => $textColumnIds,
            'cellDisplayValues' => $this->buildAccountManagementDisplayValues($budgetTable, $columnsByPosition),
            'budgetTypeCost' => $this->getMainPositionsByBudgetType(
                $budgetTable,
                BudgetTypeEnum::BUDGET_TYPE_COST
            ),
            'budgetTypeEarning' => $this->getMainPositionsByBudgetType(
                $budgetTable,
                BudgetTypeEnum::BUDGET_TYPE_EARNING
            )
        ];
    }

    /**
     * Bei aktiver Kontenverwaltung: "Nummer – Name" für KTO-/KST-Zellen (Spiegel von
     * BudgetService::enrichAccountManagementDisplayValues), keyed by Zellen-Id.
     *
     * @return array<int, string>
     */
    private function buildAccountManagementDisplayValues(?Table $budgetTable, Collection $columnsByPosition): array
    {
        if (!$budgetTable || !app(GeneralSettings::class)->budget_account_management_global) {
            return [];
        }

        $ktoColumnId = (int) ($columnsByPosition->get(0)?->id ?? 0);
        $kstColumnId = (int) ($columnsByPosition->get(1)?->id ?? 0);
        if (!$ktoColumnId || !$kstColumnId) {
            return [];
        }

        $cellsByColumn = ['kto' => [], 'kst' => []];
        foreach ($budgetTable->mainPositions as $mainPosition) {
            foreach ($mainPosition->subPositions as $subPosition) {
                foreach ($subPosition->subPositionRows as $row) {
                    foreach ($row->cells as $cell) {
                        $rawValue = trim((string) ($cell->value ?? ''));
                        if ($rawValue === '') {
                            continue;
                        }
                        if ((int) $cell->column_id === $ktoColumnId) {
                            $cellsByColumn['kto'][$cell->id] = $rawValue;
                        } elseif ((int) $cell->column_id === $kstColumnId) {
                            $cellsByColumn['kst'][$cell->id] = $rawValue;
                        }
                    }
                }
            }
        }

        $accountTitles = empty($cellsByColumn['kto'])
            ? collect()
            : BudgetManagementAccount::query()
                ->whereIn('account_number', array_values(array_unique($cellsByColumn['kto'])))
                ->pluck('title', 'account_number');
        $costUnitTitles = empty($cellsByColumn['kst'])
            ? collect()
            : BudgetManagementCostUnit::query()
                ->whereIn('cost_unit_number', array_values(array_unique($cellsByColumn['kst'])))
                ->pluck('title', 'cost_unit_number');

        $displayValues = [];
        foreach ($cellsByColumn['kto'] as $cellId => $number) {
            if ($accountTitles->has($number)) {
                $displayValues[(int) $cellId] = $number . ' – ' . $accountTitles->get($number);
            }
        }
        foreach ($cellsByColumn['kst'] as $cellId => $number) {
            if ($costUnitTitles->has($number)) {
                $displayValues[(int) $cellId] = $number . ' – ' . $costUnitTitles->get($number);
            }
        }

        return $displayValues;
    }

    private function getMainPositionsByBudgetType(
        Model $budgetTable,
        BudgetTypeEnum $mainPositionBudgetType
    ): Collection {
        return $budgetTable->mainPositions->filter(
            fn($mainPosition) => $mainPosition->type === $mainPositionBudgetType->value
        );
    }

    /**
     * @return array<int, array<string, array<string, mixed>>>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
    public function styles(Worksheet $sheet): array
    {
        return [
            //first row bold
            1 => ['font' => ['bold' => true]]
        ];
    }
}
