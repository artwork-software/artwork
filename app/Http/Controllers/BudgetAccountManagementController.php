<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Services\BudgetManagementAccountService;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\Budget\Services\BudgetManagementCostUnitService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class BudgetAccountManagementController extends Controller
{
    public function __construct(
        private readonly BudgetManagementAccountService $budgetManagementAccountService,
        private readonly BudgetManagementCostUnitService $budgetManagementCostUnitService
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', BudgetManagementAccount::class);
        $this->authorize('viewAny', BudgetManagementCostUnit::class);

        return Inertia::render(
            'BudgetSettingsAccountManagement/Index',
            [
                'accounts' => $this->budgetManagementAccountService->getAllOrderedByIsAccountForRevenue(),
                'cost_units' => $this->budgetManagementCostUnitService->getAll()
            ]
        );
    }
}
