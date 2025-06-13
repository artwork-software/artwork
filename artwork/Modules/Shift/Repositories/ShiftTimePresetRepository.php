<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\ShiftTimePreset;

class ShiftTimePresetRepository extends BaseRepository
{
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return ShiftTimePreset::all();
    }
}
