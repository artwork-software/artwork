<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

readonly class SageBookingDataDeleteService
{
    public function __construct(
        private SageNotAssignedDataService $sageNotAssignedDataService,
        private SageAssignedDataService $sageAssignedDataService
    ) {
    }

    public function deleteByBookingCriteria(
        ?string $ktr,
        ?string $dateFrom,
        ?string $dateTo,
        bool $deleteAssignedData = false
    ): void {
        $dateFromFormatted = $dateFrom ? Carbon::parse($dateFrom)->format('Y-m-d') : null;
        $dateToFormatted = $dateTo ? Carbon::parse($dateTo)->addDay()->format('Y-m-d') : null;

        $this->deleteSageNotAssignedDataInRange($ktr, $dateFromFormatted, $dateToFormatted);

        if ($deleteAssignedData) {
            $this->deleteSageAssignedDataInRange($ktr, $dateFromFormatted, $dateToFormatted);
        }
    }

    private function applyBookingCriteria(Builder $query, ?string $ktr, ?string $dateFrom, ?string $dateTo): Builder
    {
        if ($ktr !== null && $ktr !== '') {
            $query->where('kst_traeger', '=', $ktr);
        }
        if ($dateFrom !== null && $dateTo !== null) {
            $query->where('buchungsdatum', '>=', $dateFrom)
                ->where('buchungsdatum', '<', $dateTo);
        }

        return $query;
    }

    private function deleteSageNotAssignedDataInRange(?string $ktr, ?string $dateFrom, ?string $dateTo): void
    {
        $sageNotAssignedDataToDelete = $this->applyBookingCriteria(
            SageNotAssignedData::query(),
            $ktr,
            $dateFrom,
            $dateTo
        )->get();

        foreach ($sageNotAssignedDataToDelete as $data) {
            if ($data->is_collective_booking) {
                foreach ($data->findChildren()->get() as $child) {
                    $this->sageNotAssignedDataService->forceDelete($child);
                }
            }
            $this->sageNotAssignedDataService->forceDelete($data);
        }
    }

    private function deleteSageAssignedDataInRange(?string $ktr, ?string $dateFrom, ?string $dateTo): void
    {
        $sageAssignedDataToDelete = $this->applyBookingCriteria(
            SageAssignedData::query(),
            $ktr,
            $dateFrom,
            $dateTo
        )->get();

        foreach ($sageAssignedDataToDelete as $data) {
            if ($data->is_collective_booking) {
                $this->sageAssignedDataService->deleteChildData($data);
            }
            $this->sageAssignedDataService->delete($data);
        }
    }
}
