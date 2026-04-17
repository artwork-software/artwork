<?php

namespace App\Http\Controllers;

use Artwork\Modules\GeneralSettings\Http\Requests\UpdateBudgetAccountManagementGlobalRequest;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class GeneralSettingsController extends Controller
{
    public function __construct(private readonly GeneralSettingsService $generalSettingsService)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function updateBudgetAccountManagementGlobal(
        UpdateBudgetAccountManagementGlobalRequest $request
    ): RedirectResponse {
        $this->authorize('updateBudgetAccountManagementGlobal', GeneralSettings::class);

        $this->generalSettingsService->updateBudgetAccountManagementGlobalFromRequest($request);

        return Redirect::back();
    }

    public function inventoryGeneral(): \Inertia\Response
    {
        $generalSettings = app(GeneralSettings::class);

        return Inertia::render('InventorySetting/General', [
            'inventoryDetailedArticlesAlwaysQuantityOne' =>
                $generalSettings->inventory_detailed_articles_always_quantity_one,
        ]);
    }

    public function updateInventoryDetailedArticlesAlwaysQuantityOne(Request $request): RedirectResponse
    {
        $this->generalSettingsService->updateInventoryDetailedArticlesAlwaysQuantityOne($request);

        return Redirect::back();
    }
}
