<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SumComment;
use Artwork\Modules\Budget\Repositories\SumCommentRepository;

class SumCommentService
{
    public function __construct(
        private readonly SumCommentRepository $sumCommentRepository
    ) {
    }

    public function forceDelete(SumComment $sumComment): void
    {
        $this->sumCommentRepository->forceDelete($sumComment);
    }
}
