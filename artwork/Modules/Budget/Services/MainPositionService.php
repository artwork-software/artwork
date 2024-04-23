<?php

namespace Artwork\Modules\Budget\Services;

use App\Enums\BudgetTypesEnum;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\MainPositionVerified;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Repositories\MainPositionRepository;

class MainPositionService
{
    public function __construct(
        private readonly MainPositionRepository $mainPositionRepository,
        private readonly SubPositionService $subPositionService,
        private readonly MainPositionVerifiedService $mainPositionVerifiedService,
        private readonly MainPositionDetailsService $mainPositionDetailsService
    ) {
    }

    public function createMainPosition(
        Table $table,
        BudgetTypesEnum $budgetTypesEnum,
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
        ColumnCellService $columnCellService
    ): void {
        if (($mainPositionVerified = $mainPosition->verified) instanceof MainPositionVerified) {
            $this->mainPositionVerifiedService->forceDelete($mainPositionVerified);
        }

        $mainPosition->mainPositionSumDetails->each(function (MainPositionDetails $mainPositionDetails): void {
            $this->mainPositionDetailsService->forceDelete($mainPositionDetails);
        });

        $mainPosition->subPositions->each(
            function (SubPosition $subPosition) use (
                $sumCommentService,
                $sumMoneySourceService,
                $subPositionVerifiedService,
                $subPositionSumDetailService,
                $subPositionRowService,
                $rowCommentService,
                $columnCellService
            ): void {
                $this->subPositionService->forceDelete(
                    $subPosition,
                    $sumCommentService,
                    $sumMoneySourceService,
                    $subPositionVerifiedService,
                    $subPositionSumDetailService,
                    $subPositionRowService,
                    $rowCommentService,
                    $columnCellService
                );
            }
        );

        $this->mainPositionRepository->forceDelete($mainPosition);
    }
}
