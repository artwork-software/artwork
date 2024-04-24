<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Repositories\SageNotAssignedDataRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Redirect;

readonly class SageNotAssignedDataService
{
    public function __construct(private SageNotAssignedDataRepository $sageNotAssignedDataRepository,)
    {
    }

    public function create(array $attributes): SageNotAssignedData
    {
        $sageNotAssignedData = new SageNotAssignedData($attributes);

        $this->sageNotAssignedDataRepository->save($sageNotAssignedData);

        return $sageNotAssignedData;
    }

    public function update(SageNotAssignedData $sageNotAssignedData, array $attributes): SageNotAssignedData
    {
        $this->sageNotAssignedDataRepository->update($sageNotAssignedData, $attributes);

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

    public function findBySageId(int $sageId): SageNotAssignedData|null
    {
        return $this->sageNotAssignedDataRepository->findBySageId($sageId);
    }

    public function delete(SageNotAssignedData $sageNotAssignedData): void
    {
        $this->sageNotAssignedDataRepository->delete($sageNotAssignedData);
    }

    public function forceDelete(SageNotAssignedData $sageNotAssignedData): bool
    {
        return $this->sageNotAssignedDataRepository->forceDelete($sageNotAssignedData);
    }

    public function getTrashed(): Collection
    {
        return $this->sageNotAssignedDataRepository->getTrashed();
    }

    public function restore(SageNotAssignedData $sageNotAssignedData): bool
    {
        return $this->sageNotAssignedDataRepository->restore($sageNotAssignedData);
    }

    public function moveSageData(SageNotAssignedData $sageNotAssignedData, ColumnCell $columnCell)
    {
        // get all cells on the same row
        $columnCells = $columnCell->subPositionRow->cells()->get();

        // check if any cell in $columnCells has a value with the same
        // $sageNotAssignedData->sa_kto as $columnCell->value in the first three columns
        $cellWithSameSaKto = $columnCells->first(
            fn (ColumnCell $cell) => $cell->value === $sageNotAssignedData->sa_kto
        );

        // check if any cell in $columnCells has a value with the same
        // $sageNotAssignedData->kst_stelle as $columnCell->value
        $cellWithSameKstStelle = $columnCells->first(
            fn (ColumnCell $cell) => $cell->value === $sageNotAssignedData->kst_stelle
        );

        // now we can check if $cellWithSameSaKto and $cellWithSameKstStelle are not null
        // and if they are not null we can check if they are not the same cell
        if ($cellWithSameSaKto && $cellWithSameKstStelle) {
            // create a new SageAssignedData with the values from $sageNotAssignedData
            // and the values from $cellWithSameSaKto and $cellWithSameKstStelle

            // now bind the $sageAssignedData to $columnCell
            $columnCell->sageAssignedData()->create([
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
                'buchungsdatum' => $sageNotAssignedData->buchungsdatum,
            ]);

            $currentCellValue = $columnCell->value;
            $columnCell->update(
                ['value' => floatval(str_replace(',', '.', $columnCell->value)) +
                    floatval(str_replace(',', '.', $sageNotAssignedData->buchungsbetrag))
                ]
            );

            $this->forceDelete($sageNotAssignedData);

            return Redirect::back();
        } else {
            return Redirect::back()->with(
                'error',
                __('flash-messages.budget-drag-and-drop.error.drop')
            );
        }
    }
}
