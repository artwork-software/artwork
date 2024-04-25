<?php

namespace Artwork\Modules\Budget\Exports;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
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
                'columns',
                'mainPositions',
                'mainPositions.subPositions' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions.subPositionRows' => function ($query) {
                    return $query->orderBy('position');
                },
                'mainPositions.subPositions.subPositionRows.cells',
                'mainPositions.subPositions.subPositionRows.cells.sageAssignedData',
            ])
            ->first();

        return [
            'budgetTable' => $budgetTable,
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
