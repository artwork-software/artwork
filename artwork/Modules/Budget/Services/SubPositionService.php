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

    public function delete(SubPosition $subPosition): void
    {
        if (($subPositionVerified = $subPosition->verified) instanceof SubPositionVerified) {
            $this->subPositionVerifiedService->delete($subPositionVerified);
        }

        $subPosition->subPositionSumDetails->each(function (SubPositionSumDetail $subPositionSumDetail): void {
            $this->subPositionSumDetailService->delete($subPositionSumDetail);
        });

        $subPosition->subPositionRows->each(function (SubPositionRow $subPositionRow): void {
            $this->subPositionRowService->delete($subPositionRow);
        });

        $this->subPositionRepository->delete($subPosition);
    }
}
