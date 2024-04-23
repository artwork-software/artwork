<?php

namespace Artwork\Modules\BudgetManagementAccount\Services;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\BudgetManagementAccount\Http\Requests\StoreBudgetManagementAccountRequest;
use Artwork\Modules\BudgetManagementAccount\Http\Requests\UpdateBudgetManagementAccountRequest;
use Artwork\Modules\BudgetManagementAccount\Models\BudgetManagementAccount;
use Artwork\Modules\BudgetManagementAccount\Repositories\BudgetManagementAccountRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Throwable;

readonly class BudgetManagementAccountService
{
    public function __construct(private BudgetManagementAccountRepository $budgetManagementAccountRepository)
    {
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
        return $this->budgetManagementAccountRepository->getByAccountNumberOrTitleAndIsAccountForRevenue(
            $request->get('search'),
            $request->boolean('is_account_for_revenue')
        );
    }

    /**
     * @throws Throwable
     */
    public function createFromRequest(StoreBudgetManagementAccountRequest $request): BudgetManagementAccount
    {
        $budgetManagementAccount = new BudgetManagementAccount($request->validated());

        $this->budgetManagementAccountRepository->saveOrFail($budgetManagementAccount);

        return $budgetManagementAccount;
    }

    /**
     * @throws Throwable
     */
    public function updateFromRequest(
        BudgetManagementAccount $budgetManagementAccount,
        UpdateBudgetManagementAccountRequest $request
    ): BudgetManagementAccount {
        $this->budgetManagementAccountRepository->updateOrFail($budgetManagementAccount, $request->validated());

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

    public function forceDelete(
        BudgetManagementAccount $budgetManagementAccount,
        ProjectService $projectService,
        ColumnCellService $columnCellService
    ): void {
        //set all according column cells to 00000
        /** @var Project $project */
        foreach ($projectService->getAll() as $project) {
            $firstColumnId = $project->table->columns()->orderBy('id')->first()->id;

            $project->table->mainPositions->each(
                function (MainPosition $mainPosition) use (
                    $firstColumnId,
                    $budgetManagementAccount,
                    $columnCellService
                ): void {
                    $mainPosition->subPositions->each(
                        function (SubPosition $subPosition) use (
                            $firstColumnId,
                            $budgetManagementAccount,
                            $columnCellService
                        ): void {
                            $subPosition->subPositionRows->each(
                                function (SubPositionRow $subPositionRow) use (
                                    $firstColumnId,
                                    $budgetManagementAccount,
                                    $columnCellService
                                ): void {
                                    $columnCell = $subPositionRow->cells
                                        ->where('column_id', $firstColumnId)
                                        ->first();

                                    if ($columnCell->value === $budgetManagementAccount->account_number) {
                                        $columnCellService->updateValue($columnCell, '00000');
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
