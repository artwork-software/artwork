<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;

readonly class SageAssignedDataCommentRepository extends BaseRepository
{
    public function getById(int $id): SageAssignedDataComment|null
    {
        return SageAssignedDataComment::find($id);
    }
}
