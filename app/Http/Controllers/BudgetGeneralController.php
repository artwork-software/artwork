<?php

namespace App\Http\Controllers;

use Artwork\Modules\BudgetColumnSetting\Http\Requests\UpdateBudgetColumnSettingRequest;
use Artwork\Modules\BudgetColumnSetting\Models\BudgetColumnSetting;
use Artwork\Modules\BudgetColumnSetting\Services\BudgetColumnSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BudgetGeneralController extends Controller
{
    public function __construct(private readonly BudgetColumnSettingService $budgetColumnSettingService)
    {
        $this->authorizeResource(BudgetColumnSetting::class, 'budgetColumnSetting');
    }

    public function index(): Response
    {
        return Inertia::render(
            'BudgetSettingsGeneral/Index',
            [
                'budgetColumnSettings' => $this->budgetColumnSettingService->getAll()
            ]
        );
    }

    public function update(
        UpdateBudgetColumnSettingRequest $updateBudgetColumnSettingRequest,
        BudgetColumnSetting $budgetColumnSetting
    ): RedirectResponse {
        try {
            $this->budgetColumnSettingService->updateFromRequest(
                $budgetColumnSetting,
                $updateBudgetColumnSettingRequest
            );
        } catch (\Throwable $t) {
            Log::error('Not able to save budget general setting for reason: ' . $t->getMessage());

            return Redirect::back()->with('error', __('flash-messages.budget-general-setting.error.update'));
        }

        return Redirect::back()->with('success', __('flash-messages.budget-general-setting.success.update'));
    }
}
