<?php

namespace Artwork\Modules\BusinessIntelligence\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BusinessIntelligence\Models\BiTimeEffort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class BiTimeEffortRepository extends BaseRepository
{
    public function __construct(
        private readonly BiTimeEffort $biTimeEffort
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->biTimeEffort->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->biTimeEffort->newModelQuery();
    }

    public function getByProjectId(int $projectId): Collection
    {
        return $this->getNewModelQuery()
            ->where('project_id', $projectId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }
}
