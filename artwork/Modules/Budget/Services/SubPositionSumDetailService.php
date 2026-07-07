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

    public function softDelete(
        SubPositionSumDetail $subPositionSumDetail,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        $subPositionSumDetail->comments->each(function (SumComment $sumComment) use ($sumCommentService): void {
            $sumCommentService->softDelete($sumComment);
        });

        if (($sumMoneySource = $subPositionSumDetail->sumMoneySource) instanceof SumMoneySource) {
            $sumMoneySourceService->softDelete($sumMoneySource);
        }

        $this->subPositionSumDetailRepository->delete($subPositionSumDetail);
    }

    public function restore(
        SubPositionSumDetail $subPositionSumDetail,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        $subPositionSumDetail->comments()->withTrashed()->get()->each(
            fn (SumComment $sumComment) => $sumCommentService->restore($sumComment)
        );

        $sumMoneySource = $subPositionSumDetail->sumMoneySource()->withTrashed()->first();
        if ($sumMoneySource instanceof SumMoneySource) {
            $sumMoneySourceService->restore($sumMoneySource);
        }

        $this->subPositionSumDetailRepository->restore($subPositionSumDetail);
    }
}
