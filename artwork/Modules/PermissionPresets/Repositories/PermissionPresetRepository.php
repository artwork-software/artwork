<?php

namespace Artwork\Modules\PermissionPresets\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\PermissionPresets\Http\Requests\StorePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Http\Requests\UpdatePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class PermissionPresetRepository extends BaseRepository
{
    public function getPermissionPresets(): Collection
    {
        return PermissionPreset::all(['id', 'name', 'permissions']);
    }

    /**
     * @throws Throwable
     */
    public function createFromRequest(StorePermissionPresetRequest $storePermissionPresetRequest): void
    {
        $permissionPreset = new PermissionPreset();
        $permissionPreset->fill([
            'name' => $storePermissionPresetRequest->get('name'),
            'permissions' => $storePermissionPresetRequest->get('permissions')
        ]);
        $permissionPreset->saveOrFail();
    }

    /**
     * @throws Throwable
     */
    public function updateFromRequest(
        UpdatePermissionPresetRequest $updatePermissionPresetRequest,
        PermissionPreset $permissionPreset
    ): void {
        $permissionPreset->updateOrFail(
            [
                'name' => $updatePermissionPresetRequest->get('name'),
                'permissions' => $updatePermissionPresetRequest->get('permissions')
            ]
        );
    }
}
