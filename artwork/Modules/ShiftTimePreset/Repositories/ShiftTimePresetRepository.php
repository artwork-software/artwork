<?php

namespace Artwork\Modules\ShiftTimePreset\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ShiftTimePreset\Models\ShiftTimePreset;

readonly class ShiftTimePresetRepository extends BaseRepository
{
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return ShiftTimePreset::all();
    }
}
