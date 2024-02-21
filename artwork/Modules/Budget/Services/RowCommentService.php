<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Repositories\RowCommentRepository;

class RowCommentService
{
    public function __construct(
        private readonly RowCommentRepository $rowCommentRepository
    ) {
    }

    public function delete(RowComment $rowComment): void
    {
        $this->rowCommentRepository->delete($rowComment);
    }
}
