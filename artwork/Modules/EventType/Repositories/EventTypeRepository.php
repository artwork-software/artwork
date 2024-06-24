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

    public function getById(int $id): ?EventType
    {
        return EventType::find($id);
    }

    public function getByName(string $name): ?EventType
    {
        return EventType::where('name', '=', $name)->first();
    }

    public function getFallbackEventType(): ?EventType
    {
        return EventType::where('fallback_type', true)->first();
    }
}
