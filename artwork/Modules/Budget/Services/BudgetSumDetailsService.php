<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\BudgetSumDetailsRepository;

readonly class BudgetSumDetailsService
{
    public function __construct(private BudgetSumDetailsRepository $budgetSumDetailsRepository)
    {
    }

    public function forceDelete(
        BudgetSumDetails $budgetSumDetails,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        $budgetSumDetails->comments->each(function (SumComment $sumComment) use ($sumCommentService): void {
            $sumCommentService->forceDelete($sumComment);
        });

        if (($sumMoneySource = $budgetSumDetails->sumMoneySource) instanceof SumMoneySource) {
            $sumMoneySourceService->forceDelete($sumMoneySource);
        }

        $this->budgetSumDetailsRepository->forceDelete($budgetSumDetails);
    }
}
