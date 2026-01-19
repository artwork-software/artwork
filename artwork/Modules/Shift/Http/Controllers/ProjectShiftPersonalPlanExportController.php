<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Exports\ProjectShiftPersonalPlanExcelExport;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectShiftPersonalPlanExportController
{
    public function __construct(
        protected AuthManager $authManager
    ) {
    }

    public function __invoke(Project $project): BinaryFileResponse
    {
        $language = $this->authManager->user()->language;

        $fileName = sprintf(
            '%s_personal_plan_%s.xlsx',
            str_replace(' ', '_', $project->name),
            now()->format('Y-m-d')
        );

        return new ProjectShiftPersonalPlanExcelExport($project, $language)
            ->download($fileName);
    }
}
