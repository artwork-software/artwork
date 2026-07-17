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
        // 'Y-m' ohne Tag erbt den heutigen Monatstag (am 31. → Overflow in den Folgemonat),
        // daher explizit auf den Monatsersten parsen
        $rangeStart = Carbon::createFromFormat('Y-m-d', $validated['start_month'] . '-01')->startOfMonth();
        $rangeEnd = Carbon::createFromFormat('Y-m-d', $validated['end_month'] . '-01')->endOfMonth();
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
