<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Carbon\Carbon;

readonly class SageBookingDataDeleteService
{
    public function __construct(
        private SageNotAssignedDataService $sageNotAssignedDataService,
        private SageAssignedDataService $sageAssignedDataService
    ) {
    }

    public function deleteByBookingDateRange(
        string $dateFrom,
        string $dateTo,
        bool $deleteAssignedData = false
    ): void {
        $dateFromFormatted = Carbon::parse($dateFrom)->format('Y-m-d');
        $dateToFormatted = Carbon::parse($dateTo)->addDay()->format('Y-m-d');

        $this->deleteSageNotAssignedDataInRange($dateFromFormatted, $dateToFormatted);

        if ($deleteAssignedData) {
            $this->deleteSageAssignedDataInRange($dateFromFormatted, $dateToFormatted);
        }
    }

    private function deleteSageNotAssignedDataInRange(string $dateFrom, string $dateTo): void
    {
        $sageNotAssignedDataToDelete = SageNotAssignedData::query()
            ->where('buchungsdatum', '>=', $dateFrom)
            ->where('buchungsdatum', '<', $dateTo)
            ->get();

        foreach ($sageNotAssignedDataToDelete as $data) {
            if ($data->is_collective_booking) {
                foreach ($data->findChildren()->get() as $child) {
                    $this->sageNotAssignedDataService->forceDelete($child);
                }
            }
            $this->sageNotAssignedDataService->forceDelete($data);
        }
    }

    private function deleteSageAssignedDataInRange(string $dateFrom, string $dateTo): void
    {
        $sageAssignedDataToDelete = SageAssignedData::query()
            ->where('buchungsdatum', '>=', $dateFrom)
            ->where('buchungsdatum', '<', $dateTo)
            ->get();

        foreach ($sageAssignedDataToDelete as $data) {
            if ($data->is_collective_booking) {
                $this->sageAssignedDataService->deleteChildData($data);
            }
            $this->sageAssignedDataService->delete($data);
        }
    }
}
