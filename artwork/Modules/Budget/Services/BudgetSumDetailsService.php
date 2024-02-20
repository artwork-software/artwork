<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Repositories\BudgetSumDetailsRepository;

class BudgetSumDetailsService
{
    public function __construct(
        private readonly BudgetSumDetailsRepository $budgetSumDetailsRepository,
        private readonly SumCommentService $sumCommentService,
        private readonly SumMoneySourceService $sumMoneySourceService
    ) {
    }

    public function delete(BudgetSumDetails $budgetSumDetails): void
    {
        $budgetSumDetails->comments->each(function (SumComment $sumComment): void {
            $this->sumCommentService->delete($sumComment);
        });

        if (($sumMoneySource = $budgetSumDetails->sumMoneySource) instanceof SumMoneySource) {
            $this->sumMoneySourceService->delete($sumMoneySource);
        }

        $this->budgetSumDetailsRepository->delete($budgetSumDetails);
    }
}
