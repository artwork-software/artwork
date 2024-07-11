<?php

namespace Artwork\Modules\Checklist\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Checklist\Models\Checklist;

class ChecklistRepository extends BaseRepository
{
    public function getById(int $id): mixed
    {
        return Checklist::find($id);
    }
}
