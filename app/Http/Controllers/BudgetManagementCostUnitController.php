<?php

namespace App\Http\Controllers;

use Artwork\Modules\BudgetManagementCostUnit\Http\Requests\StoreBudgetManagementCostUnitRequest;
use Artwork\Modules\BudgetManagementCostUnit\Models\BudgetManagementCostUnit;
use Artwork\Modules\BudgetManagementCostUnit\Services\BudgetManagementCostUnitService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Throwable;

class BudgetManagementCostUnitController extends Controller
{
    public function __construct(
        private readonly BudgetManagementCostUnitService $budgetManagementCostUnitService
    ) {
        $this->authorizeResource(BudgetManagementCostUnit::class, 'budgetManagementCostUnit');
    }

    public function store(StoreBudgetManagementCostUnitRequest $storeBudgetManagementCostUnitRequest): RedirectResponse
    {
        try {
            $this->budgetManagementCostUnitService->createFromRequest($storeBudgetManagementCostUnitRequest);
        } catch (\Throwable $t) {
            Log::error('Can not create budget management cost unit for reason: ' . $t->getMessage());

            return Redirect::back()->with(
                'error',
                __('flash-messages.budget-account-management.error.cost-unit.create')
            );
        }

        return Redirect::back()->with(
            'success',
            __('flash-messages.budget-account-management.success.cost-unit.create')
        );
    }

    public function destroy(BudgetManagementCostUnit $budgetManagementCostUnit): RedirectResponse
    {
        try {
            $this->budgetManagementCostUnitService->delete($budgetManagementCostUnit);
        } catch (Throwable $t) {
            Log::error('Can not delete budget management cost_unit for reason: ' . $t->getMessage());

            return Redirect::back()->with(
                'error',
                __('flash-messages.budget-account-management.error.cost-unit.delete')
            );
        }

        return Redirect::back()->with(
            'success',
            __('flash-messages.budget-account-management.success.cost-unit.delete')
        );
    }

    public function restore(BudgetManagementCostUnit $budgetManagementCostUnit): RedirectResponse
    {
        $this->budgetManagementCostUnitService->restore($budgetManagementCostUnit);

        return Redirect::back();
    }

    public function forceDelete(BudgetManagementCostUnit $budgetManagementCostUnit): RedirectResponse
    {
        $this->budgetManagementCostUnitService->forceDelete($budgetManagementCostUnit);

        return Redirect::back();
    }
}
