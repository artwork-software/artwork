<?php

namespace Artwork\Modules\EventType\Repositories;

use App\Models\EventType;
use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class EventTypeRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return EventType::all();
    }
}
