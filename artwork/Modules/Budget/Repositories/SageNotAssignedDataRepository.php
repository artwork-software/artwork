<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Illuminate\Database\Eloquent\Collection;

class SageNotAssignedDataRepository extends BaseRepository
{
    public function getTrashed(): Collection
    {
        return SageNotAssignedData::onlyTrashed()->get();
    }

    public function restore(SageNotAssignedData $sageNotAssignedData): bool
    {
        return $sageNotAssignedData->restore();
    }
}
