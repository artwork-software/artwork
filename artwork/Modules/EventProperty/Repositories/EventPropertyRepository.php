<?php

namespace Artwork\Modules\EventProperty\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\EventProperty\Models\EventProperty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class EventPropertyRepository extends BaseRepository
{
    public function __construct(
        private readonly EventProperty $eventProperty
    ) {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->eventProperty->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->eventProperty->newModelQuery();
    }

    public function getAll(): Collection
    {
        return EventProperty::all();
    }
}
