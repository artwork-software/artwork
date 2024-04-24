<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Repositories\RowCommentRepository;

readonly class RowCommentService
{
    public function __construct(private RowCommentRepository $rowCommentRepository)
    {
    }

    public function forceDelete(RowComment $rowComment): void
    {
        $this->rowCommentRepository->forceDelete($rowComment);
    }
}
