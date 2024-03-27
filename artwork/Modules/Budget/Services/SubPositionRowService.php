<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Repositories\SubPositionRowRepository;

class SubPositionRowService
{
    public function __construct(
        private readonly SubPositionRowRepository $subPositionRowRepository,
        private readonly RowCommentService $rowCommentService,
        private readonly ColumnCellService $columnCellService
    ) {
    }

    public function forceDelete(SubPositionRow $subPositionRow): void
    {
        $subPositionRow->comments->each(function (RowComment $rowComment): void {
            $this->rowCommentService->forceDelete($rowComment);
        });

        $subPositionRow->cells->each(function (ColumnCell $columnCell): void {
            $this->columnCellService->forceDelete($columnCell);
        });

        $this->subPositionRowRepository->forceDelete($subPositionRow);
    }
}
