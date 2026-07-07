<?php

namespace Artwork\Modules\BusinessIntelligence\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class BiProjectDataRepository extends BaseRepository
{
    public function __construct(
        private readonly BiProjectData $biProjectData
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->biProjectData->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->biProjectData->newModelQuery();
    }

    public function findByProjectId(int $projectId): ?BiProjectData
    {
        return $this->getNewModelQuery()->where('project_id', $projectId)->first();
    }

    public function firstOrCreateForProject(int $projectId): BiProjectData
    {
        return BiProjectData::firstOrCreate(['project_id' => $projectId]);
    }
}
