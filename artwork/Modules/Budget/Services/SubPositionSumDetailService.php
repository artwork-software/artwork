<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\SubPositionSumDetailRepository;

class SubPositionSumDetailService
{
    public function __construct(
        private readonly SubPositionSumDetailRepository $subPositionSumDetailRepository,
        private readonly SumCommentService $sumCommentService,
        private readonly SumMoneySourceService $sumMoneySourceService
    ) {
    }

    public function forceDelete(SubPositionSumDetail $subPositionSumDetail): void
    {
        $subPositionSumDetail->comments->each(function (SumComment $sumComment): void {
            $this->sumCommentService->forceDelete($sumComment);
        });

        if (($sumMoneySource = $subPositionSumDetail->sumMoneySource) instanceof SumMoneySource) {
            $this->sumMoneySourceService->forceDelete($sumMoneySource);
        }

        $this->subPositionSumDetailRepository->forceDelete($subPositionSumDetail);
    }
}
