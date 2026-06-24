<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Illuminate\Database\Eloquent\Collection;

class ShiftPresetRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return ShiftPreset::all();
    }

    public function getAllWithEventTypesShiftsAndTimeline(): Collection
    {
        return ShiftPreset::with([
            'shifts',
            'shifts.craft',
            'shifts.shiftsQualifications',
            //'timeline'
        ])->get();
    }

    public function findByName(string $name): Collection
    {
        return ShiftPreset::byNameLike($name)->get();
    }
}
