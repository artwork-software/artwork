<?php

namespace App\Http\Controllers;

use Artwork\Modules\BudgetManagementAccount\Http\Requests\StoreBudgetManagementAccountRequest;
use Artwork\Modules\BudgetManagementAccount\Models\BudgetManagementAccount;
use Artwork\Modules\BudgetManagementAccount\Services\BudgetManagementAccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BudgetManagementAccountController extends Controller
{
    public function __construct(
        private readonly BudgetManagementAccountService $budgetManagementAccountService
    ) {
        $this->authorizeResource(BudgetManagementAccount::class, 'budgetManagementAccount');
    }

    public function indexTrash(): Response
    {
        return Inertia::render(
            'Trash/BudgetManagementAccount',
            [
                'trashedAccounts' => $this->budgetManagementAccountService->getAllTrashed()
            ]
        );
    }

    public function store(StoreBudgetManagementAccountRequest $storeBudgetManagementAccountRequest): RedirectResponse
    {
        try {
            $this->budgetManagementAccountService->createFromRequest($storeBudgetManagementAccountRequest);
        } catch (Throwable $t) {
            Log::error('Can not create budget management account for reason: ' . $t->getMessage());

            return Redirect::back()->with(
                'error',
                __('flash-messages.budget-account-management.error.account.create')
            );
        }

        return Redirect::back()->with(
            'success',
            __('flash-messages.budget-account-management.success.account.create')
        );
    }

    public function destroy(BudgetManagementAccount $budgetManagementAccount): RedirectResponse
    {
        try {
            $this->budgetManagementAccountService->delete($budgetManagementAccount);
        } catch (Throwable $t) {
            Log::error('Can not delete budget management account for reason: ' . $t->getMessage());

            return Redirect::back()->with(
                'error',
                __('flash-messages.budget-account-management.error.account.delete')
            );
        }

        return Redirect::back()->with(
            'success',
            __('flash-messages.budget-account-management.success.account.delete')
        );
    }

    public function restore(BudgetManagementAccount $budgetManagementAccount): RedirectResponse
    {
        $this->budgetManagementAccountService->restore($budgetManagementAccount);

        return Redirect::back();
    }

    public function forceDelete(BudgetManagementAccount $budgetManagementAccount): RedirectResponse
    {
        $this->budgetManagementAccountService->forceDelete($budgetManagementAccount);

        return Redirect::back();
    }
}
