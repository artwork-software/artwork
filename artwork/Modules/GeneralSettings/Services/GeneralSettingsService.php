<?php

namespace Artwork\Modules\GeneralSettings\Services;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Modules\GeneralSettings\Dto\FileHandlingDto;
use Artwork\Modules\GeneralSettings\Http\Requests\UpdateBudgetAccountManagementGlobalRequest;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Http\Request;

class GeneralSettingsService
{
    public function __construct(private readonly GeneralSettings $generalSettings)
    {
    }

    public function updateBudgetAccountManagementGlobalFromRequest(
        UpdateBudgetAccountManagementGlobalRequest $request
    ): void {
        $this->generalSettings->budget_account_management_global = $request->get('enabled');

        $this->generalSettings->save();
    }
    
    public function updateAllowedProjectFileMimeTypesFromRequest(
        Request $request
    ): void {
        $this->generalSettings->allowed_project_file_mimetypes = $request->get('mime_types');

        $this->generalSettings->save();
    }
    
    public function getAllowedProjectFileMimeTypes(): array
    {
        return $this->generalSettings->allowed_project_file_mimetypes;
    }
    
    public function getAllowedRoomFileMimeTypes(): array
    {
        return $this->generalSettings->allowed_room_file_mimetypes;
    }
    
    public function getAllowedBrandingFileMimeTypes(): array
    {
        return $this->generalSettings->allowed_branding_file_mimetypes;
    }
}
