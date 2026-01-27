<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Services\SageAssignedDataService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class SageAssignedDataController extends Controller
{
    public function __construct(
        private readonly SageAssignedDataService $sageAssignedDataService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService
    ) {
        $this->authorizeResource(SageAssignedData::class, 'sageAssignedData');
    }

    public function destroy(SageAssignedData $sageAssignedData): RedirectResponse
    {
        $columnCell = $sageAssignedData->columnCell;
        $projectId = $columnCell?->subPositionRow?->subPosition?->mainPosition?->table?->project_id;

        /*
         * check if other SageAssignedData entities exist by sage_id, except the given one
         * if multiple are found we iterate through and forceDelete them, right after a global SageNotAssignedData
         * entity was created - it means "sage_id" was also assigned to one or more project group(s)
         * if not given SageAssignedData is moved to SageNotAssignedData as project related
         */
        $assignedSageDataBySageIdExcluded = $this->sageAssignedDataService->findAllBySageIdExcluded(
            $sageAssignedData->getAttribute('sage_id'),
            [$sageAssignedData->getAttribute('id')]
        );

        if ($assignedSageDataBySageIdExcluded->count() > 0) {
            $this->sageNotAssignedDataService->createFromSageAssignedData($sageAssignedData);
            $this->sageAssignedDataService->forceDelete($sageAssignedData);

            foreach ($assignedSageDataBySageIdExcluded as $assignedSageData) {
                $this->sageAssignedDataService->forceDelete($assignedSageData);
            }
        } else {
            $this->sageNotAssignedDataService->createFromSageAssignedData(
                $sageAssignedData,
                $projectId
            );
            $this->sageAssignedDataService->forceDelete($sageAssignedData);
        }

        return Redirect::back();
    }
}
