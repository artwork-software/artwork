<?php

namespace Artwork\Modules\Event\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Collection;

class EventRepository extends BaseRepository
{
    public function getEventsByProjectIdAndEventTypeId(int $projectId, int $eventTypeId): Collection
    {
        return Event::byProjectId($projectId)->byEventTypeId($eventTypeId)->get();
    }
}
