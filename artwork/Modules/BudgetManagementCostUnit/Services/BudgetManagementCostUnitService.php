<?php

namespace Artwork\Modules\BudgetManagementCostUnit\Services;

use Artwork\Modules\BudgetManagementCostUnit\Http\Requests\StoreBudgetManagementCostUnitRequest;
use Artwork\Modules\BudgetManagementCostUnit\Models\BudgetManagementCostUnit;
use Artwork\Modules\BudgetManagementCostUnit\Repositories\BudgetManagementCostUnitRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

readonly class BudgetManagementCostUnitService
{
    public function __construct(
        private BudgetManagementCostUnitRepository $budgetManagementCostUnitRepository
    ) {
    }

    public function getAll(): Collection
    {
        return $this->budgetManagementCostUnitRepository->getAll();
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
    public function delete(BudgetManagementCostUnit $budgetManagementCostUnit): void
    {
        $this->budgetManagementCostUnitRepository->deleteOrFail($budgetManagementCostUnit);
    }

    public function restore(BudgetManagementCostUnit $budgetManagementCostUnit): void
    {
        $this->budgetManagementCostUnitRepository->restore($budgetManagementCostUnit);
    }

    public function forceDelete(BudgetManagementCostUnit $budgetManagementCostUnit): void
    {
        $this->budgetManagementCostUnitRepository->forceDelete($budgetManagementCostUnit);
    }
}
