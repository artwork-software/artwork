<?php

namespace Artwork\Modules\BusinessIntelligence\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectRoomCapacity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class BiProjectRoomCapacityRepository extends BaseRepository
{
    public function __construct(
        private readonly BiProjectRoomCapacity $biProjectRoomCapacity
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->biProjectRoomCapacity->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->biProjectRoomCapacity->newModelQuery();
    }

    public function getByProjectId(int $projectId): Collection
    {
        return $this->getNewModelQuery()
            ->where('project_id', $projectId)
            ->with('room')
            ->get();
    }

    public function upsert(int $projectId, int $roomId, ?int $capacityOverride): BiProjectRoomCapacity
    {
        return BiProjectRoomCapacity::updateOrCreate(
            ['project_id' => $projectId, 'room_id' => $roomId],
            ['capacity_override' => $capacityOverride]
        );
    }
}
