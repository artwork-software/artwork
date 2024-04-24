<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\SubPositionSumDetailRepository;

readonly class SubPositionSumDetailService
{
    public function __construct(private SubPositionSumDetailRepository $subPositionSumDetailRepository)
    {
    }

    public function forceDelete(
        SubPositionSumDetail $subPositionSumDetail,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        $subPositionSumDetail->comments->each(function (SumComment $sumComment) use ($sumCommentService): void {
            $sumCommentService->forceDelete($sumComment);
        });

        if (($sumMoneySource = $subPositionSumDetail->sumMoneySource) instanceof SumMoneySource) {
            $sumMoneySourceService->forceDelete($sumMoneySource);
        }

        $this->subPositionSumDetailRepository->forceDelete($subPositionSumDetail);
    }
}
