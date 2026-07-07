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


    // soft delete function like forceDelete but soft delete
    public function softDelete(
        BudgetSumDetails $budgetSumDetails,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        $budgetSumDetails->comments->each(function (SumComment $sumComment) use ($sumCommentService): void {
            $sumCommentService->softDelete($sumComment);
        });

        if (($sumMoneySource = $budgetSumDetails->sumMoneySource) instanceof SumMoneySource) {
            $sumMoneySourceService->softDelete($sumMoneySource);
        }

        $this->budgetSumDetailsRepository->delete($budgetSumDetails);
    }

    public function restore(
        BudgetSumDetails $budgetSumDetails,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService
    ): void {
        $budgetSumDetails->comments()->withTrashed()->get()->each(
            fn (SumComment $sumComment) => $sumCommentService->restore($sumComment)
        );

        $sumMoneySource = $budgetSumDetails->sumMoneySource()->withTrashed()->first();
        if ($sumMoneySource instanceof SumMoneySource) {
            $sumMoneySourceService->restore($sumMoneySource);
        }

        $this->budgetSumDetailsRepository->restore($budgetSumDetails);
    }
}
