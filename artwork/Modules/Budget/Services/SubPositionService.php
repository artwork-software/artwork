<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\SubPositionVerified;
use Artwork\Modules\Budget\Repositories\SubPositionRepository;

readonly class SubPositionService
{
    public function __construct(private SubPositionRepository $subPositionRepository)
    {
    }

    public function forceDelete(
        SubPosition $subPosition,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): void {
        if (($subPositionVerified = $subPosition->verified) instanceof SubPositionVerified) {
            $subPositionVerifiedService->forceDelete($subPositionVerified);
        }

        $subPosition->subPositionSumDetails->each(
            function (SubPositionSumDetail $subPositionSumDetail) use (
                $sumCommentService,
                $sumMoneySourceService,
                $subPositionSumDetailService
            ): void {
                $subPositionSumDetailService->forceDelete(
                    $subPositionSumDetail,
                    $sumCommentService,
                    $sumMoneySourceService
                );
            }
        );

        $subPosition->subPositionRows->each(
            function (SubPositionRow $subPositionRow) use (
                $subPositionRowService,
                $rowCommentService,
                $columnCellService,
                $cellCommentService,
                $cellCalculationService,
                $sageNotAssignedDataService,
                $sageAssignedDataService
            ): void {
                $subPositionRowService->forceDelete(
                    $subPositionRow,
                    $rowCommentService,
                    $columnCellService,
                    $cellCommentService,
                    $cellCalculationService,
                    $sageNotAssignedDataService,
                    $sageAssignedDataService
                );
            }
        );

        $this->subPositionRepository->forceDelete($subPosition);
    }
}
