<?php

namespace App\Exports;

use App\Enums\BudgetTypesEnum;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectBudgetExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    /**
     * @param Project $project
     */
    public function __construct(private readonly Project $project)
    {
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view('exports.projectBudget', ['data' => $this->getData()]);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $budgetTable = $this->project->table()
            ->with([
                'columns',
                'mainPositions',
                'mainPositions.subPositions' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions.subPositionRows' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions.subPositionRows.cells',
                'mainPositions.subPositions.subPositionRows.cells.column'
            ])
            ->first();

        return [
            'budgetTable' => $budgetTable,
            'budgetTypeCost' => $this->getMainPositionsByBudgetType(
                $budgetTable,
                BudgetTypesEnum::BUDGET_TYPE_COST
            ),
            'budgetTypeEarning' => $this->getMainPositionsByBudgetType(
                $budgetTable,
                BudgetTypesEnum::BUDGET_TYPE_EARNING
            )
        ];
    }

    /**
     * @param Model $budgetTable
     * @param BudgetTypesEnum $mainPositionBudgetType
     * @return Collection
     */
    private function getMainPositionsByBudgetType(
        Model           $budgetTable,
        BudgetTypesEnum $mainPositionBudgetType
    ): Collection
    {
        return $budgetTable->mainPositions->filter(
            fn($mainPosition) => $mainPosition->type === $mainPositionBudgetType->value
        );
    }

    /**
     * @param Worksheet $sheet
     * @return array[]
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            //first row bold
            1 => ['font' => ['bold' => true]]
        ];
    }
}
