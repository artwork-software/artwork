<?php

namespace Artwork\Modules\Permission\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Permission\Models\PermissionPreset;
use Illuminate\Database\Eloquent\Collection;

class PermissionPresetRepository extends BaseRepository
{
    public function getPermissionPresets(): Collection
    {
        return PermissionPreset::all(['id', 'name', 'permissions']);
    }
}
