<?php

namespace Artwork\Modules\Event\Repositories;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Collection;

trait FindsEventsWithoutRoom
{
    public function getEventsWithoutRoom(int|Project|null $project = null, array|null $with = null): Collection
    {
        return $this->eventRepository->getEventsWithoutRoom($project, $with);
    }
}
