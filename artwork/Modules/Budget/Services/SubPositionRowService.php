<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Repositories\SubPositionRowRepository;

readonly class SubPositionRowService
{
    public function __construct(private SubPositionRowRepository $subPositionRowRepository)
    {
    }

    public function forceDelete(
        SubPositionRow $subPositionRow,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): void {
        $subPositionRow->comments->each(function (RowComment $rowComment) use ($rowCommentService): void {
            $rowCommentService->forceDelete($rowComment);
        });

        $subPositionRow->cells->each(
            function (ColumnCell $columnCell) use (
                $columnCellService,
                $cellCommentService,
                $cellCalculationService,
                $sageNotAssignedDataService,
                $sageAssignedDataService
            ): void {
                $columnCellService->forceDelete(
                    $columnCell,
                    $cellCommentService,
                    $cellCalculationService,
                    $sageNotAssignedDataService,
                    $sageAssignedDataService
                );
            }
        );

        $this->subPositionRowRepository->forceDelete($subPositionRow);
    }
}
