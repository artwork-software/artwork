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
