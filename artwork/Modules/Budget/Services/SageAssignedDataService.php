<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Repositories\SageAssignedDataRepository;

readonly class SageAssignedDataService
{
    public function __construct(private SageAssignedDataRepository $sageAssignedDataRepository)
    {
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
        array $sageApiData
    ): SageAssignedData {
        $attributes = [
            'column_cell_id' => $columnCellId,
            'sage_id' => $sageApiData['ID'],
            'tan' => $sageApiData['TAN'],
            'kreditor' => $sageApiData['Kreditor'],
            'buchungstext' => $sageApiData['Buchungstext'],
            'buchungsbetrag' => $sageApiData['Buchungsbetrag'],
            'belegnummer' => $sageApiData['Belegnummer'],
            'belegdatum' => $sageApiData['Belegdatum'],
            'sa_kto' => $sageApiData['SaKto'],
            'kst_traeger' => $sageApiData['KstTraeger'],
            'kst_stelle' => $sageApiData['KstStelle'],
            'buchungsdatum' => $sageApiData['Buchungsdatum'],
        ];

        return $this->create($attributes);
    }

    public function createFromSageNotAssignedData(
        int $columnCellId,
        SageNotAssignedData $sageNotAssignedData,
        SageNotAssignedDataService $sageNotAssignedDataService
    ): SageAssignedData {
        $sageAssignedData = $this->create([
            'column_cell_id' => $columnCellId,
            'sage_id' => $sageNotAssignedData->sage_id,
            'tan' => $sageNotAssignedData->tan,
            'kreditor' => $sageNotAssignedData->kreditor,
            'buchungstext' => $sageNotAssignedData->buchungstext,
            'buchungsbetrag' => $sageNotAssignedData->buchungsbetrag,
            'belegnummer' => $sageNotAssignedData->belegnummer,
            'belegdatum' => $sageNotAssignedData->belegdatum,
            'sa_kto' => $sageNotAssignedData->sa_kto,
            'kst_traeger' => $sageNotAssignedData->kst_traeger,
            'kst_stelle' => $sageNotAssignedData->kst_stelle,
            'buchungsdatum' => $sageNotAssignedData->buchungsdatum
        ]);

        $sageNotAssignedDataService->forceDelete($sageNotAssignedData);

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
}
