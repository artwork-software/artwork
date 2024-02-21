<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Repositories\ColumnCellRepository;

class ColumnCellService
{
    public function __construct(
        private readonly ColumnCellRepository $columnCellRepository,
        private readonly CellCommentService $cellCommentService,
        private readonly CellCalculationService $cellCalculationsService,
        private readonly SageNotAssignedDataService $sageNotAssignedDataService,
        private readonly SageAssignedDataService $sageAssignedDataService
    ) {
    }

    public function delete(ColumnCell $columnCell): void
    {
        $columnCell->comments->each(function (CellComment $cellComment): void {
            $this->cellCommentService->delete($cellComment);
        });

        $columnCell->calculations->each(function (CellCalculation $cellCalculation): void {
            $this->cellCalculationsService->delete($cellCalculation);
        });

        if (!$columnCell->subPositionRow->subPosition->mainPosition->table->is_template) {
            $sageAssignedData = $columnCell->sageAssignedData;
            if ($sageAssignedData instanceof SageAssignedData) {
                $this->sageNotAssignedDataService->createFromSageAssignedData(
                    $sageAssignedData,
                    $columnCell->subPositionRow->subPosition->mainPosition->table->project_id
                );

                $this->sageAssignedDataService->delete($sageAssignedData);
            }
        }

        $this->columnCellRepository->delete($columnCell);
    }

    public function resetCellCalculationsPosition(ColumnCell $columnCell): void
    {
        $columnCell->calculations->each(function ($cellCalculation, $index): void {
            $this->cellCalculationsService->update($cellCalculation, ['position' => $index]);
        });
    }
}
