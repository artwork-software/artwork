<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\BudgetColumnSetting\Http\Requests\UpdateBudgetColumnSettingRequest;
use Artwork\Modules\BudgetColumnSetting\Models\BudgetColumnSetting;
use Artwork\Modules\BudgetColumnSetting\Services\BudgetColumnSettingService;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetGeneralController extends Controller
{
    public function __construct(
        private readonly BudgetColumnSettingService $budgetColumnSettingService,
        private readonly Sage100Service $sage100Service
    ) {
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


    public function moveSageDataRow(ColumnCell $columnCell, ColumnCell $movedColumn, Request $request): void
    {
        //dd($request->all());
        $this->sage100Service->moveSageDataRow($columnCell, $movedColumn, $request);
    }
}
