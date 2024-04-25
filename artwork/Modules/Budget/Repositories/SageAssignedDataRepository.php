<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageAssignedData;

readonly class SageAssignedDataRepository extends BaseRepository
{
    public function getByColumnCellId(int $columnSubPositionRowId): SageAssignedData|null
    {
        return SageAssignedData::byColumnCellId($columnSubPositionRowId)->first();
    }

    public function findBySageId(int $sageId): SageAssignedData|null
    {
        return SageAssignedData::bySageId($sageId)->first();
    }
}
