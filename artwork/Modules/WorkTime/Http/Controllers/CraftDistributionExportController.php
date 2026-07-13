<?php

namespace Artwork\Modules\WorkTime\Http\Controllers;

use Artwork\Modules\WorkTime\Exports\CraftDistributionExcelExport;
use Artwork\Modules\WorkTime\Http\Requests\CraftDistributionExportRequest;
use Artwork\Modules\WorkTime\Services\CraftDistributionExportService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CraftDistributionExportController
{
    public function __construct(
        private readonly CraftDistributionExportService $exportService,
    ) {
    }

    public function __invoke(CraftDistributionExportRequest $request): BinaryFileResponse
    {
        $validated = $request->validated();
        $rangeStart = Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
        $rangeEnd = Carbon::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay();
        $language = $request->user()->language ?: app()->getLocale();
        $distribution = $this->exportService->buildDistribution(
            $rangeStart,
            $rangeEnd,
            (int) $validated['universal_craft_id'],
            array_map('intval', $validated['crafts'] ?? []),
        );

        return new CraftDistributionExcelExport($distribution, $rangeStart, $rangeEnd, $language)
            ->download(sprintf(
                'craft_distribution_%s_%s.xlsx',
                $rangeStart->format('Y-m-d'),
                $rangeEnd->format('Y-m-d'),
            ));
    }
}
