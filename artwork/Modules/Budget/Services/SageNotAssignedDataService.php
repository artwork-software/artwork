<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Repositories\SageNotAssignedDataRepository;
use Illuminate\Database\Eloquent\Collection;

class SageNotAssignedDataService
{
    public function __construct(
        private readonly SageNotAssignedDataRepository $sageNotAssignedDataRepository,
    ) {
    }

    public function create(array $attributes): SageNotAssignedData
    {
        $sageNotAssignedData = new SageNotAssignedData($attributes);

        $this->sageNotAssignedDataRepository->save($sageNotAssignedData);

        return $sageNotAssignedData;
    }

    public function createFromSageApiData(array $data, int|null $projectId = null): SageNotAssignedData
    {
        return $this->create([
            'project_id' => $projectId,
            'sage_id' => $data['ID'],
            'tan' => $data['TAN'],
            'kreditor' => $data['Kreditor'],
            'buchungstext' => $data['Buchungstext'],
            'buchungsbetrag' => $data['Buchungsbetrag'],
            'belegnummer' => $data['Belegnummer'],
            'belegdatum' => $data['Belegdatum'],
            'sa_kto' => $data['SaKto'],
            'kst_traeger' => $data['KstTraeger'],
            'kst_stelle' => $data['KstStelle'],
            'buchungsdatum' => $data['Buchungsdatum'],
        ]);
    }

    public function createFromSageAssignedData(
        SageAssignedData $sageAssignedData,
        int|null $projectId = null
    ): SageNotAssignedData {
        return $this->create([
            'project_id' => $projectId,
            'sage_id' => $sageAssignedData->sage_id,
            'tan' => $sageAssignedData->tan,
            'kreditor' => $sageAssignedData->kreditor,
            'buchungstext' => $sageAssignedData->buchungstext,
            'buchungsbetrag' => $sageAssignedData->buchungsbetrag,
            'belegnummer' => $sageAssignedData->belegnummer,
            'belegdatum' => $sageAssignedData->belegdatum,
            'sa_kto' => $sageAssignedData->sa_kto,
            'kst_traeger' => $sageAssignedData->kst_traeger,
            'kst_stelle' => $sageAssignedData->kst_stelle,
            'buchungsdatum' => $sageAssignedData->buchungsdatum,
        ]);
    }

    public function delete(SageNotAssignedData $sageNotAssignedData): void
    {
        $this->sageNotAssignedDataRepository->delete($sageNotAssignedData);
    }

    public function forceDelete(SageNotAssignedData $sageNotAssignedData): void
    {
        $this->sageNotAssignedDataRepository->forceDelete($sageNotAssignedData);
    }

    public function getTrashed(): Collection
    {
        return $this->sageNotAssignedDataRepository->getTrashed();
    }

    public function restore(SageNotAssignedData $sageNotAssignedData): bool
    {
        return $this->sageNotAssignedDataRepository->restore($sageNotAssignedData);
    }
}
