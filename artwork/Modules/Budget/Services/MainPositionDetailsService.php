<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\MainPositionDetailsRepository;

class MainPositionDetailsService
{
    public function __construct(
        private readonly MainPositionDetailsRepository $mainPositionDetailsRepository,
        private readonly SumCommentService $sumCommentService,
        private readonly SumMoneySourceService $sumMoneySourceService
    ) {
    }

    public function forceDelete(MainPositionDetails $mainPositionDetails): void
    {
        $mainPositionDetails->comments->each(function (SumComment $sumComment): void {
            $this->sumCommentService->forceDelete($sumComment);
        });

        if (($sumMoneySource = $mainPositionDetails->sumMoneySource) instanceof SumMoneySource) {
            $this->sumMoneySourceService->forceDelete($sumMoneySource);
        }

        $this->mainPositionDetailsRepository->forceDelete($mainPositionDetails);
    }
}
