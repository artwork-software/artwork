<?php

namespace Artwork\Modules\BusinessIntelligence\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BusinessIntelligence\Models\BiSnapshot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class BiSnapshotRepository extends BaseRepository
{
    public function __construct(
        private readonly BiSnapshot $biSnapshot
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->biSnapshot->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->biSnapshot->newModelQuery();
    }

    public function getByProjectId(int $projectId): Collection
    {
        return $this->getNewModelQuery()
            ->where('project_id', $projectId)
            ->with('creator')
            ->orderByDesc('snapshot_date')
            ->get();
    }
}
