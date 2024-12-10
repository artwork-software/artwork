<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;

class SageAssignedDataRepository extends BaseRepository
{

    public function __construct(private readonly SageAssignedData $sageAssignedData)
    {
    }

    public function getNewModelInstance(): SageAssignedData
    {
        return $this->sageAssignedData->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->sageAssignedData->newModelQuery();
    }

    public function getByColumnCellId(int $columnSubPositionRowId): SageAssignedData|null
    {
        return SageAssignedData::byColumnCellId($columnSubPositionRowId)->first();
    }

    public function findBySageId(int $sageId): SageAssignedData|null
    {
        return SageAssignedData::bySageId($sageId)->first();
    }

    public function findAllBySageIdExcluded(int $sageId, array $excluded): Collection
    {
        return $this->getNewModelQuery()->whereNotIn('id', $excluded)->where('sage_id', '=', $sageId)->get();
    }
}
