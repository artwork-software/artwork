<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CollectiveBookings\CollectiveBooking;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Repositories\SageAssignedDataRepository;
use Artwork\Modules\Budget\Services\CollectiveBookings\CollectiveBookingService;
use Artwork\Modules\Budget\Services\CollectiveBookings\HandlesCollectiveBookings;
use Illuminate\Database\Eloquent\Collection;

class SageAssignedDataService implements CollectiveBookingService
{

    use HandlesCollectiveBookings;

    public function __construct(
        private readonly SageAssignedDataRepository $sageAssignedDataRepository,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService
    ) {
    }

    public function create(array $attributes): SageAssignedData
    {
        $sageAssignedData = new SageAssignedData($attributes);

        $this->sageAssignedDataRepository->save($sageAssignedData);

        return $sageAssignedData;
    }

    public function update(SageAssignedData $sageAssignedData, array $attributes): SageAssignedData
    {
        $this->sageAssignedDataRepository->update($sageAssignedData, $attributes);

        return $sageAssignedData;
    }

    public function createFromSageApiData(
        int $columnCellId,
        array $sageApiData,
        CollectiveBooking|null $collectiveBooking = null,
    ): SageAssignedData {
        $attributes = [
            'column_cell_id' => $columnCellId,
            'sage_id' => $sageApiData['ID'],
            'tan' => $sageApiData['Tan'],
            'periode' => $sageApiData['Periode'],
            'kto_haben' => $sageApiData['KtoHaben'],
            'kreditor' => $sageApiData['Kreditor'] ?? '',
            'buchungstext' => $sageApiData['Buchungstext'],
            'buchungsbetrag' => $sageApiData['Buchungsbetrag'],
            'belegnummer' => $sageApiData['Belegnummer'],
            'belegdatum' => $sageApiData['Belegdatum'],
            'kto_soll' => $sageApiData['KtoSoll'],
            'sa_kto' => $sageApiData['SaKto'] ?? '',
            'kst_traeger' => $sageApiData['KstTraeger'],
            'kst_stelle' => $sageApiData['KstStelle'],
            'buchungsdatum' => $sageApiData['Buchungsdatum'],
            'is_collective_booking' => (bool)$collectiveBooking,
            'parent_booking_id' => $collectiveBooking?->id,
        ];

        return $this->create($attributes);
    }

    public function createFromSageNotAssignedData(
        int|null $columnCellId,
        SageNotAssignedData $sageNotAssignedData,
        CollectiveBooking|null $newParent = null
    ): SageAssignedData {
        $sageAssignedData = $this->create([
            'column_cell_id' => $columnCellId,
            'sage_id' => $sageNotAssignedData->sage_id,
            'tan' => $sageNotAssignedData->tan,
            'periode' => $sageNotAssignedData->periode,
            'kto_haben' => $sageNotAssignedData->kto_haben,
            'kreditor' => $sageNotAssignedData->kreditor,
            'buchungstext' => $sageNotAssignedData->buchungstext,
            'buchungsbetrag' => $sageNotAssignedData->buchungsbetrag,
            'belegnummer' => $sageNotAssignedData->belegnummer,
            'belegdatum' => $sageNotAssignedData->belegdatum,
            'kto_soll' => $sageNotAssignedData->kto_soll,
            'sa_kto' => $sageNotAssignedData->sa_kto,
            'kst_traeger' => $sageNotAssignedData->kst_traeger,
            'kst_stelle' => $sageNotAssignedData->kst_stelle,
            'buchungsdatum' => $sageNotAssignedData->buchungsdatum,
            'is_collective_booking' => $sageNotAssignedData->is_collective_booking,
            'parent_booking_id' => $newParent?->id,
        ]);
        if ($sageNotAssignedData->is_collective_booking) {
            foreach($sageNotAssignedData->findChildren()->get() as $child) {
                $this->createFromSageNotAssignedData(null, $child, $sageAssignedData);
            }
            $this->sageNotAssignedDataService->deleteChildData($sageNotAssignedData);
        }
        $this->sageNotAssignedDataService->forceDelete($sageNotAssignedData);

        return $sageAssignedData;
    }

    public function delete(SageAssignedData $sageAssignedData): void
    {
        $this->sageAssignedDataRepository->delete($sageAssignedData);
    }

    public function forceDelete(SageAssignedData $sageAssignedData): bool
    {
        return $this->sageAssignedDataRepository->forceDelete($sageAssignedData);
    }

    public function findBySageId(int $sageId): SageAssignedData|null
    {
        return $this->sageAssignedDataRepository->findBySageId($sageId);
    }

    public function findAllBySageIdExcluded(int $sageId, array $excludedIds): Collection
    {
        return $this->sageAssignedDataRepository->findAllBySageIdExcluded($sageId, $excludedIds);
    }

    public function forceDeleteAll(): void
    {
        $this->sageAssignedDataRepository->forceDeleteAll();
    }

    public function findParentBookingByIdentifiers(
        ...$identifiers
    ): SageAssignedData|null {
        [
            $sageId,
            $ktoSoll,
            $ktoHaben
        ] = $identifiers;

        return $this->sageAssignedDataRepository->findParentBookingBySageIdKtoSollAndKtoHaben(
            $sageId,
            $ktoSoll,
            $ktoHaben
        );
    }

}
