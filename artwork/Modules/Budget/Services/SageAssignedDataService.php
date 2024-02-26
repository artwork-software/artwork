<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Repositories\SageAssignedDataRepository;

class SageAssignedDataService
{
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

    public function createOrUpdateFromSageApiData(
        int $columnCellId,
        array $sageApiData,
        int|null $projectId
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

        //if there's already a sage_assigned_data-row for given column cell it must be written
        //to sage_not_assigned_data again
        $sageAssignedData = $this->sageAssignedDataRepository->getByColumnCellId($columnCellId);
        if ($sageAssignedData instanceof SageAssignedData) {
            $this->sageNotAssignedDataService->createFromSageAssignedData($sageAssignedData, $projectId);

            //existing row is updated then
            $this->sageAssignedDataRepository->update($sageAssignedData, $attributes);

            return $sageAssignedData;
        }

        return $this->create($attributes);
    }

    public function createFromSageNotAssignedData(
        int $columnCellId,
        SageNotAssignedData $sageNotAssignedData
    ): SageAssignedData {
        return $this->create([
            'column_cell_id' => $columnCellId,
            'sage_id' => $sageNotAssignedData->id,
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
    }

    public function delete(SageAssignedData $sageAssignedData): void
    {
        $this->sageAssignedDataRepository->delete($sageAssignedData);
    }

    public function findBySageId(int $sageId): SageAssignedData|null
    {
        return $this->sageAssignedDataRepository->findBySageId($sageId);
    }
}
