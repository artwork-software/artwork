<?php

namespace Artwork\Modules\BudgetManagementAccount\Services;

use Artwork\Modules\BudgetManagementAccount\Http\Requests\StoreBudgetManagementAccountRequest;
use Artwork\Modules\BudgetManagementAccount\Models\BudgetManagementAccount;
use Artwork\Modules\BudgetManagementAccount\Repositories\BudgetManagementAccountRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

readonly class BudgetManagementAccountService
{
    public function __construct(
        private BudgetManagementAccountRepository $budgetManagementAccountRepository
    ) {
    }

    public function getAll(): Collection
    {
        return $this->budgetManagementAccountRepository->getAll();
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
        $this->budgetManagementAccountRepository->forceDelete($budgetManagementAccount);
    }
}
