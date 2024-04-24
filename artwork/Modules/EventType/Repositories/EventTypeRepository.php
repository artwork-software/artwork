<?php

namespace Artwork\Modules\EventType\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\EventType\Models\EventType;
use Illuminate\Database\Eloquent\Collection;

readonly class EventTypeRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return EventType::all();
    }
}
