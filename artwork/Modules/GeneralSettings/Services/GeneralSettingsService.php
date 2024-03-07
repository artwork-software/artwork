<?php

namespace Artwork\Modules\GeneralSettings\Services;

use Artwork\Modules\GeneralSettings\Http\Requests\UpdateBudgetAccountManagementGlobalRequest;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;

readonly class GeneralSettingsService
{
    public function __construct(private GeneralSettings $generalSettings)
    {
    }

    public function updateBudgetAccountManagementGlobalFromRequest(
        UpdateBudgetAccountManagementGlobalRequest $request
    ): void {
        $this->generalSettings->budget_account_management_global = $request->get('enabled');

        $this->generalSettings->save();
    }
}
