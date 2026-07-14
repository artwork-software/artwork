<?php

namespace Artwork\Modules\WorkTime\Http\Controllers;

use Artwork\Modules\WorkTime\Exports\WorkTimeOverviewExcelExport;
use Artwork\Modules\WorkTime\Http\Requests\WorkTimeOverviewExportRequest;
use Artwork\Modules\WorkTime\Services\WorkTimeOverviewExportService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkTimeOverviewExportController
{
    public function __construct(
        private readonly WorkTimeOverviewExportService $exportService,
    ) {
    }

    public function __invoke(WorkTimeOverviewExportRequest $request): BinaryFileResponse
    {
        $validated = $request->validated();
        $rangeStart = Carbon::createFromFormat('Y-m', $validated['start_month'])->startOfMonth();
        $rangeEnd = Carbon::createFromFormat('Y-m', $validated['end_month'])->endOfMonth();
        $language = $request->user()->language;
        $matrix = $this->exportService->buildMatrix(
            $rangeStart,
            $rangeEnd,
            array_map('intval', $validated['crafts'] ?? []),
            $language,
        );

        return new WorkTimeOverviewExcelExport($matrix, $rangeStart, $rangeEnd, $language)
            ->download(sprintf(
                'work_time_overview_%s_%s.xlsx',
                $rangeStart->format('Y-m'),
                $rangeEnd->format('Y-m'),
            ));
    }
}
