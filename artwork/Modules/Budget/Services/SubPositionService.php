<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\SubPositionVerified;
use Artwork\Modules\Budget\Repositories\SubPositionRepository;

class SubPositionService
{
    public function __construct(
        private readonly SubPositionRepository $subPositionRepository,
        private readonly SubPositionRowService $subPositionRowService,
        private readonly SubPositionVerifiedService $subPositionVerifiedService,
        private readonly SubPositionSumDetailService $subPositionSumDetailService
    ) {
    }

    public function forceDelete(
        SubPosition $subPosition,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        if (($subPositionVerified = $subPosition->verified) instanceof SubPositionVerified) {
            $this->subPositionVerifiedService->forceDelete($subPositionVerified);
        }

        $subPosition->subPositionSumDetails->each(
            function (SubPositionSumDetail $subPositionSumDetail) use (
                $sumCommentService,
                $sumMoneySourceService
            ): void {
                $this->subPositionSumDetailService->forceDelete(
                    $subPositionSumDetail,
                    $sumCommentService,
                    $sumMoneySourceService
                );
            }
        );

        $subPosition->subPositionRows->each(function (SubPositionRow $subPositionRow): void {
            $this->subPositionRowService->forceDelete($subPositionRow);
        });

        $this->subPositionRepository->forceDelete($subPosition);
    }
}
