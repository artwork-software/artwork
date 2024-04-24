<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\BudgetManagementCostUnit\Http\Requests\StoreBudgetManagementCostUnitRequest;
use Artwork\Modules\BudgetManagementCostUnit\Http\Requests\UpdateBudgetManagementCostUnitRequest;
use Artwork\Modules\BudgetManagementCostUnit\Models\BudgetManagementCostUnit;
use Artwork\Modules\BudgetManagementCostUnit\Services\BudgetManagementCostUnitService;
use Artwork\Modules\Project\Services\ProjectService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BudgetManagementCostUnitController extends Controller
{
    public function __construct(
        private readonly BudgetManagementCostUnitService $budgetManagementCostUnitService
    ) {
        $this->authorizeResource(BudgetManagementCostUnit::class, 'budgetManagementCostUnit');
    }

    public function indexTrash(): Response
    {
        return Inertia::render(
            'Trash/BudgetManagementCostUnit',
            [
                'trashedCostUnits' => $this->budgetManagementCostUnitService->getAllTrashed()
            ]
        );
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

    public function update(
        BudgetManagementCostUnit $budgetManagementCostUnit,
        UpdateBudgetManagementCostUnitRequest $request
    ): RedirectResponse {
        try {
            $this->budgetManagementCostUnitService->updateFromRequest($budgetManagementCostUnit, $request);
        } catch (\Throwable $t) {
            Log::error('Can not create budget management cost unit for reason: ' . $t->getMessage());

            return Redirect::back()->with(
                'error',
                __('flash-messages.budget-account-management.error.cost-unit.update')
            );
        }

        return Redirect::back()->with(
            'success',
            __('flash-messages.budget-account-management.success.cost-unit.update')
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

    public function forceDelete(
        BudgetManagementCostUnit $budgetManagementCostUnit,
        ProjectService $projectService,
        ColumnCellService $columnCellService
    ): RedirectResponse {
        $this->budgetManagementCostUnitService->forceDelete(
            $budgetManagementCostUnit,
            $projectService,
            $columnCellService
        );

        return Redirect::back();
    }

    public function search(Request $request): Collection
    {
        return $this->budgetManagementCostUnitService->searchByRequest($request);
    }
}
