<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageAssignedData;

class SageAssignedDataRepository extends BaseRepository
{
    public function getByColumnCellId(int $columnSubPositionRowId): SageAssignedData|null
    {
        return SageAssignedData::byColumnCellId($columnSubPositionRowId)->first();
    }
}
