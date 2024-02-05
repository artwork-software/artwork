<?php

namespace Artwork\Modules\Event\Repositories;

use App\Models\Event;
use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class EventRepository extends BaseRepository
{
    public function getEventsByProjectIdAndEventTypeId(int $projectId, int $eventTypeId): Collection
    {
        return Event::byProjectId($projectId)->byEventTypeId($eventTypeId)->get();
    }
}
