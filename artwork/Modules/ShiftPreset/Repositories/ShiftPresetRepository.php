<?php

namespace Artwork\Modules\ShiftPreset\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Illuminate\Database\Eloquent\Collection;

readonly class ShiftPresetRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return ShiftPreset::all();
    }

    public function getAllWithEventTypesShiftsAndTimeline(): Collection
    {
        return ShiftPreset::with([
            'eventType',
            'shifts',
            'shifts.craft',
            'shifts.shiftsQualifications',
            'timeline'
        ])->get();
    }

    public function findByNameAndEventTypeId(string $name, int $eventTypeId): Collection
    {
        return ShiftPreset::byNameLike($name)->byEventTypeId($eventTypeId)->get();
    }
}
