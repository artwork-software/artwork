<?php

namespace Artwork\Modules\Project\Http\Controllers;

use Artwork\Modules\Project\Exports\ProjectRoleMatrixExcelExport;
use Artwork\Modules\Project\Http\Requests\ProjectRoleMatrixExportRequest;
use Artwork\Modules\Project\Services\ProjectRoleMatrixExportService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectRoleMatrixExportController
{
    public function __construct(
        private readonly ProjectRoleMatrixExportService $exportService,
    ) {
    }

    public function __invoke(ProjectRoleMatrixExportRequest $request): BinaryFileResponse
    {
        $validated = $request->validated();
        $rangeStart = Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
        $rangeEnd = Carbon::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay();
        $language = $request->user()->language ?: app()->getLocale();
        $matrix = $this->exportService->buildMatrix($rangeStart, $rangeEnd);

        return new ProjectRoleMatrixExcelExport($matrix, $rangeStart, $rangeEnd, $language)
            ->download(sprintf(
                'project_role_matrix_%s_%s.xlsx',
                $rangeStart->format('Y-m-d'),
                $rangeEnd->format('Y-m-d'),
            ));
    }
}
