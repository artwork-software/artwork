<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Repositories\SumCommentRepository;

readonly class SumCommentService
{
    public function __construct(private SumCommentRepository $sumCommentRepository)
    {
    }

    public function forceDelete(SumComment $sumComment): void
    {
        $this->sumCommentRepository->forceDelete($sumComment);
    }
}
