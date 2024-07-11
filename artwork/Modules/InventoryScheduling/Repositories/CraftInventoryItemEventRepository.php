<?php

namespace Artwork\Modules\InventoryScheduling\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class CraftInventoryItemEventRepository extends BaseRepository
{

    public function __construct(
        private readonly CraftInventoryItemEvent $craftInventoryItemEvent,
    ) {
    }

    public function getNewModelInstance(array $attributes = []): CraftInventoryItemEvent
    {
        return $this->craftInventoryItemEvent->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->craftInventoryItemEvent->newModelQuery();

        return $builder;
    }

    public function findEventByEventId(int $eventId): ?CraftInventoryItemEvent
    {
        /** @var CraftInventoryItemEvent|null $craftInventoryItemEvent */
        $craftInventoryItemEvent = $this->getNewModelQuery()
            ->where('event_id', $eventId)
            ->first();

        return $craftInventoryItemEvent;
    }
}
