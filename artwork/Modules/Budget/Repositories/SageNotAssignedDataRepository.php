<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Illuminate\Database\Eloquent\Collection;

readonly class SageNotAssignedDataRepository extends BaseRepository
{
    public function getTrashed(): Collection
    {
        return SageNotAssignedData::onlyTrashed()->get();
    }

    public function findBySageId(int $sageId): SageNotAssignedData|null
    {
        return SageNotAssignedData::where('sage_id', $sageId)->first();
    }
}
