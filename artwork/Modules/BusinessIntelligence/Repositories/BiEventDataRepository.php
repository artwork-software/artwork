<?php

namespace Artwork\Modules\BusinessIntelligence\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BusinessIntelligence\Models\BiEventData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class BiEventDataRepository extends BaseRepository
{
    public function __construct(
        private readonly BiEventData $biEventData
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->biEventData->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->biEventData->newModelQuery();
    }

    public function getByProjectId(int $projectId, string $scope = 'actual'): Collection
    {
        return $this->getNewModelQuery()
            ->where('project_id', $projectId)
            ->where('scope', $scope)
            ->with('event')
            ->get();
    }

    public function upsert(int $projectId, int $eventId, array $data, string $scope = 'actual'): BiEventData
    {
        return BiEventData::updateOrCreate(
            ['project_id' => $projectId, 'event_id' => $eventId, 'scope' => $scope],
            $data
        );
    }

    public function deleteByProjectId(int $projectId): void
    {
        $this->getNewModelQuery()->where('project_id', $projectId)->delete();
    }
}
