<?php

namespace App\Http\Controllers;

use Artwork\Modules\GeneralSettings\Http\Requests\UpdateBudgetAccountManagementGlobalRequest;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

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
}
