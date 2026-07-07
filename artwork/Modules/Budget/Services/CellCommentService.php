<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Repositories\CellCommentRepository;

readonly class CellCommentService
{
    public function __construct(private CellCommentRepository $cellCommentRepository)
    {
    }

    public function delete(CellComment $cellComment): void
    {
        $this->cellCommentRepository->delete($cellComment);
    }

    public function forceDelete(CellComment $cellComment): void
    {
        $this->cellCommentRepository->forceDelete($cellComment);
    }

    // restore function
    public function restore(CellComment $cellComment): void
    {
        $this->cellCommentRepository->restore($cellComment);
    }
}
