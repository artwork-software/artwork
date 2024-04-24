<?php

namespace Artwork\Modules\PermissionPresets\Services;

use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Artwork\Modules\PermissionPresets\Http\Requests\StorePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Http\Requests\UpdatePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Artwork\Modules\PermissionPresets\Repositories\PermissionPresetRepository;
use Illuminate\Database\Eloquent\Collection;
use Artwork\Modules\Permission\Models\Permission;
use Throwable;

readonly class PermissionPresetService
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private PermissionPresetRepository $permissionPresetRepository
    ) {
    }

    public function getPermissionPresets(): Collection
    {
        return $this->permissionPresetRepository->getPermissionPresets();
    }

    public function getAvailablePermissions(): Collection
    {
        return $this->permissionRepository->getPermissionsGroupedByPermissionGroup();
    }

    /**
     * @throws Throwable
     */
    public function createFromRequest(StorePermissionPresetRequest $storePermissionPresetRequest): void
    {
        $this->permissionPresetRepository->saveOrFail(
            new PermissionPreset([
                'name' => $storePermissionPresetRequest->get('name'),
                'permissions' => $storePermissionPresetRequest->get('permissions')
            ])
        );
    }

    /**
     * @throws Throwable
     */
    public function updateFromRequest(
        UpdatePermissionPresetRequest $updatePermissionPresetRequest,
        PermissionPreset $permissionPreset
    ): void {
        $this->permissionPresetRepository->updateOrFail(
            $permissionPreset,
            [
                'name' => $updatePermissionPresetRequest->get('name'),
                'permissions' => $updatePermissionPresetRequest->get('permissions')
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function destroy(PermissionPreset $permissionPreset): void
    {
        $this->permissionPresetRepository->deleteOrFail($permissionPreset);
    }
}
