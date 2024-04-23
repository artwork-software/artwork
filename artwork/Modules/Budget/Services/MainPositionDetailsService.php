<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\MainPositionDetailsRepository;

readonly class MainPositionDetailsService
{
    public function __construct(private MainPositionDetailsRepository $mainPositionDetailsRepository)
    {
    }

    public function forceDelete(
        MainPositionDetails $mainPositionDetails,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        $mainPositionDetails->comments->each(
            function (SumComment $sumComment) use ($sumCommentService): void {
                $sumCommentService->forceDelete($sumComment);
            }
        );

        if (($sumMoneySource = $mainPositionDetails->sumMoneySource) instanceof SumMoneySource) {
            $sumMoneySourceService->forceDelete($sumMoneySource);
        }

        $this->mainPositionDetailsRepository->forceDelete($mainPositionDetails);
    }
}
