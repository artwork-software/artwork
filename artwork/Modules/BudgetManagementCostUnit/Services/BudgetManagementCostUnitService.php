<?php

namespace Artwork\Modules\BudgetManagementCostUnit\Services;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\BudgetManagementCostUnit\Http\Requests\StoreBudgetManagementCostUnitRequest;
use Artwork\Modules\BudgetManagementCostUnit\Http\Requests\UpdateBudgetManagementCostUnitRequest;
use Artwork\Modules\BudgetManagementCostUnit\Models\BudgetManagementCostUnit;
use Artwork\Modules\BudgetManagementCostUnit\Repositories\BudgetManagementCostUnitRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Throwable;

readonly class BudgetManagementCostUnitService
{
    public function __construct(private BudgetManagementCostUnitRepository $budgetManagementCostUnitRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->budgetManagementCostUnitRepository->getAll();
    }

    public function getAllTrashed(): Collection
    {
        return $this->budgetManagementCostUnitRepository->getAllTrashed();
    }

    public function searchByRequest(Request $request): Collection
    {
        return $this->budgetManagementCostUnitRepository->getByCostUnitNumberOrTitle($request->get('search'));
    }

    /**
     * @throws Throwable
     */
    public function createFromRequest(
        StoreBudgetManagementCostUnitRequest $storeBudgetManagementCostUnitRequest
    ): BudgetManagementCostUnit {
        $budgetManagementCostUnit = new BudgetManagementCostUnit(
            $storeBudgetManagementCostUnitRequest->validated()
        );

        $this->budgetManagementCostUnitRepository->saveOrFail($budgetManagementCostUnit);

        return $budgetManagementCostUnit;
    }

    /**
     * @throws Throwable
     */
    public function updateFromRequest(
        BudgetManagementCostUnit $budgetManagementCostUnit,
        UpdateBudgetManagementCostUnitRequest $request
    ): BudgetManagementCostUnit {
        $this->budgetManagementCostUnitRepository->updateOrFail($budgetManagementCostUnit, $request->validated());

        return $budgetManagementCostUnit;
    }

    /**
     * @throws Throwable
     */
    public function delete(BudgetManagementCostUnit $budgetManagementCostUnit): void
    {
        $this->budgetManagementCostUnitRepository->deleteOrFail($budgetManagementCostUnit);
    }

    public function restore(BudgetManagementCostUnit $budgetManagementCostUnit): void
    {
        $this->budgetManagementCostUnitRepository->restore($budgetManagementCostUnit);
    }

    public function forceDelete(
        BudgetManagementCostUnit $budgetManagementCostUnit,
        ProjectService $projectService,
        ColumnCellService $columnCellService
    ): void {
        //set all according column cells to 00000
        /** @var Project $project */
        foreach ($projectService->getAll() as $project) {
            $secondColumnId = $project->table
                ->columns()
                ->orderBy('id')
                ->get()
                ->splice(1, 1)
                ->first()
                ->id;

            $project->table->mainPositions->each(
                function (MainPosition $mainPosition) use (
                    $secondColumnId,
                    $budgetManagementCostUnit,
                    $columnCellService
                ): void {
                    $mainPosition->subPositions->each(
                        function (SubPosition $subPosition) use (
                            $secondColumnId,
                            $budgetManagementCostUnit,
                            $columnCellService
                        ): void {
                            $subPosition->subPositionRows->each(
                                function (SubPositionRow $subPositionRow) use (
                                    $secondColumnId,
                                    $budgetManagementCostUnit,
                                    $columnCellService
                                ): void {
                                    $columnCell = $subPositionRow->cells
                                        ->where('column_id', $secondColumnId)
                                        ->first();

                                    if ($columnCell->value === $budgetManagementCostUnit->cost_unit_number) {
                                        $columnCellService->updateValue($columnCell, '00000');
                                    }
                                }
                            );
                        }
                    );
                }
            );
        }
        $this->budgetManagementCostUnitRepository->forceDelete($budgetManagementCostUnit);
    }
}
