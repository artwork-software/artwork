<?php

namespace Artwork\Modules\BusinessIntelligence\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class BiEventTypeTagRepository extends BaseRepository
{
    public function __construct(
        private readonly BiEventTypeTag $biEventTypeTag
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->biEventTypeTag->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->biEventTypeTag->newModelQuery();
    }

    public function getAll(): Collection
    {
        return $this->getNewModelQuery()->get();
    }

    public function getAllWithEventTypes(): Collection
    {
        return $this->getNewModelQuery()->with('eventTypes')->get();
    }
}
