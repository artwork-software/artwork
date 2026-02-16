<?php

namespace Artwork\Modules\GeneralSettings\Services;

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

    public function updateAllowedFileMimeTypesFromRequest(
        Request $request
    ): void {
        $fileTypes = $this->extractFileTypes($request);
        $mimeProperty = $this->assembleMimetypePropertyName($request->input('data')['name']);
        $fileSizeProperty = $this->assembleFilesizePropertyName($request->input('data')['name']);
        $this->generalSettings->$mimeProperty = $fileTypes;
        $this->generalSettings->$fileSizeProperty = $request->input('data')['fileSize'];
        $this->generalSettings->save();
    }

    private function extractFileTypes(Request $request): array
    {
        $fileTypes = [];
        foreach ($request->input('data')['fileTypes'] as $fileType) {
            $fileTypes[] = $fileType['name'];
        }

        return $fileTypes;
    }

    private function assembleMimetypePropertyName($name): string
    {
        return sprintf("allowed_%s_file_mimetypes", $name);
    }

    private function assembleFilesizePropertyName($name): string
    {
        return sprintf("allowed_%s_file_size", $name);
    }

    public function getAllowedProjectFileMimeTypes(): array
    {
        return [
            'mime_types' => $this->generalSettings->allowed_project_file_mimetypes,
            'file_size' => $this->generalSettings->allowed_project_file_size
        ];
    }

    public function getAllowedRoomFileMimeTypes(): array
    {
        return [
            'mime_types' => $this->generalSettings->allowed_room_file_mimetypes,
            'file_size' => $this->generalSettings->allowed_room_file_size
        ];
    }

    public function getAllowedBrandingFileMimeTypes(): array
    {
        return [
            'mime_types' => $this->generalSettings->allowed_branding_file_mimetypes,
            'file_size' => $this->generalSettings->allowed_branding_file_size
        ];
    }
    public function getAllowedContractFileMimeTypes(): array
    {
        return [
            'mime_types' => $this->generalSettings->allowed_contract_file_mimetypes,
            'file_size' => $this->generalSettings->allowed_contract_file_size
        ];
    }


    /* Update $event_time_length_minutes in GeneralSettings from Request */
    public function updateEventTimeLengthMinutesFromRequest(Request $request): void
    {
        $this->generalSettings->event_time_length_minutes = $request->get('event_time_length_minutes');
        $this->generalSettings->save();
    }

    /* Update $event_start_time in GeneralSettings from Request */
    public function updateEventStartTimeFromRequest(Request $request): void
    {
        $this->generalSettings->event_start_time = $request->get('event_start_time') ?? '09:00';
        $this->generalSettings->save();
    }

    function updateWarningMultipleAssignmentsFromRequest(Request $request): void
    {
        $this->generalSettings->warn_multiple_assignments = $request->get('warn_multiple_assignments');
        $this->generalSettings->save();
    }
}
