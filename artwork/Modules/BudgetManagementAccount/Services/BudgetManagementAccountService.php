<?php

namespace Artwork\Modules\BudgetManagementAccount\Services;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\BudgetManagementAccount\Http\Requests\StoreBudgetManagementAccountRequest;
use Artwork\Modules\BudgetManagementAccount\Models\BudgetManagementAccount;
use Artwork\Modules\BudgetManagementAccount\Repositories\BudgetManagementAccountRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Throwable;

readonly class BudgetManagementAccountService
{
    public function __construct(
        private BudgetManagementAccountRepository $budgetManagementAccountRepository,
        private ProjectService $projectService,
        private ColumnCellService $columnCellService
    ) {
    }

    public function getAllOrderedByIsAccountForRevenue(): Collection
    {
        return $this->budgetManagementAccountRepository->getAllOrderedByIsAccountForRevenue();
    }

    public function getAllTrashed(): Collection
    {
        return $this->budgetManagementAccountRepository->getAllTrashed();
    }

    public function searchByRequest(Request $request): Collection
    {
        return $this->budgetManagementAccountRepository->getByCostUnitNumberOrTitleAndIsAccountForRevenue(
            $request->get('search'),
            $request->boolean('is_account_for_revenue')
        );
    }

    /**
     * @throws Throwable
     */
    public function createFromRequest(
        StoreBudgetManagementAccountRequest $storeBudgetManagementAccountRequest
    ): BudgetManagementAccount {
        $budgetManagementAccount = new BudgetManagementAccount(
            $storeBudgetManagementAccountRequest->validated()
        );

        $this->budgetManagementAccountRepository->saveOrFail($budgetManagementAccount);

        return $budgetManagementAccount;
    }

    /**
     * @throws Throwable
     */
    public function delete(BudgetManagementAccount $budgetManagementAccount): void
    {
        $this->budgetManagementAccountRepository->deleteOrFail($budgetManagementAccount);
    }

    public function restore(BudgetManagementAccount $budgetManagementAccount): void
    {
        $this->budgetManagementAccountRepository->restore($budgetManagementAccount);
    }

    public function forceDelete(BudgetManagementAccount $budgetManagementAccount): void
    {
        //set all according column cells to 00000
        /** @var Project $project */
        foreach ($this->projectService->getAll() as $project) {
            $firstColumnId = $project->table->columns()->orderBy('id')->first()->id;

            $project->table->mainPositions->each(
                function (MainPosition $mainPosition) use ($firstColumnId, $budgetManagementAccount): void {
                    $mainPosition->subPositions->each(
                        function (SubPosition $subPosition) use ($firstColumnId, $budgetManagementAccount): void {
                            $subPosition->subPositionRows->each(
                                function (SubPositionRow $subPositionRow) use (
                                    $firstColumnId,
                                    $budgetManagementAccount
                                ): void {
                                    $columnCell = $subPositionRow->cells
                                        ->where('column_id', $firstColumnId)
                                        ->first();

                                    if ($columnCell->value === $budgetManagementAccount->account_number) {
                                        $this->columnCellService->updateValue($columnCell, '00000');
                                    }
                                }
                            );
                        }
                    );
                }
            );
        }

        $this->budgetManagementAccountRepository->forceDelete($budgetManagementAccount);
    }
}
