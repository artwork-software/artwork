<?php

namespace Artwork\Modules\PermissionPresets\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Illuminate\Database\Eloquent\Collection;

readonly class PermissionPresetRepository extends BaseRepository
{
    public function getPermissionPresets(): Collection
    {
        return PermissionPreset::all(['id', 'name', 'permissions']);
    }
}
