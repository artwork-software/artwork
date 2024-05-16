<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\MainPositionVerified;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Repositories\MainPositionRepository;

readonly class MainPositionService
{
    public function __construct(private MainPositionRepository $mainPositionRepository)
    {
    }

    public function createMainPosition(
        Table $table,
        BudgetTypeEnum $budgetTypesEnum,
        string $name,
        int $position
    ): MainPosition|Model {
        $mainPosition = new MainPosition();
        $mainPosition->table_id = $table->id;
        $mainPosition->type = $budgetTypesEnum->value;
        $mainPosition->name = $name;
        $mainPosition->position = $position;

        return $this->mainPositionRepository->save($mainPosition);
    }

    public function forceDelete(
        MainPosition $mainPosition,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): void {
        if (($mainPositionVerified = $mainPosition->verified) instanceof MainPositionVerified) {
            $mainPositionVerifiedService->forceDelete($mainPositionVerified);
        }

        $mainPosition->mainPositionSumDetails->each(
            function (MainPositionDetails $mainPositionDetails) use (
                $mainPositionDetailsService,
                $sumCommentService,
                $sumMoneySourceService
            ): void {
                $mainPositionDetailsService->forceDelete(
                    $mainPositionDetails,
                    $sumCommentService,
                    $sumMoneySourceService
                );
            }
        );

        $mainPosition->subPositions->each(
            function (SubPosition $subPosition) use (
                $sumCommentService,
                $sumMoneySourceService,
                $subPositionVerifiedService,
                $subPositionSumDetailService,
                $subPositionRowService,
                $rowCommentService,
                $columnCellService,
                $subPositionService,
                $cellCommentService,
                $cellCalculationService,
                $sageNotAssignedDataService,
                $sageAssignedDataService
            ): void {
                $subPositionService->forceDelete(
                    $subPosition,
                    $sumCommentService,
                    $sumMoneySourceService,
                    $subPositionVerifiedService,
                    $subPositionSumDetailService,
                    $subPositionRowService,
                    $rowCommentService,
                    $columnCellService,
                    $cellCommentService,
                    $cellCalculationService,
                    $sageNotAssignedDataService,
                    $sageAssignedDataService
                );
            }
        );

        $this->mainPositionRepository->forceDelete($mainPosition);
    }
}
