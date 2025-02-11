<?php

namespace Artwork\Modules\Event\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class EventStatusRepository extends BaseRepository
{
    public function __construct(private readonly EventStatus $eventStatus)
    {
    }

    public function getNewModelInstance(array $attributes = []): EventStatus
    {
        return $this->eventStatus->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->eventStatus->newModelQuery();

        return $builder;
    }

    public function removeDefaultStatus(): void
    {
        EventStatus::where('default', true)->update(['default' => false]);
    }
}
