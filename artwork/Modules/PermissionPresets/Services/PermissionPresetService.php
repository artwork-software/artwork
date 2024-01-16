<?php

namespace Artwork\Modules\PermissionPresets\Services;

use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Artwork\Modules\PermissionPresets\Http\Requests\StorePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Http\Requests\UpdatePermissionPresetRequest;
use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Artwork\Modules\PermissionPresets\Repositories\PermissionPresetRepository;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Throwable;

class PermissionPresetService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly PermissionPresetRepository $permissionPresetRepository
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
        $this->permissionPresetRepository->createFromRequest($storePermissionPresetRequest);
    }

    /**
     * @throws Throwable
     */
    public function updateFromRequest(
        UpdatePermissionPresetRequest $updatePermissionPresetRequest,
        PermissionPreset $permissionPreset
    ): void {
        $this->permissionPresetRepository->updateFromRequest($updatePermissionPresetRequest, $permissionPreset);
    }

    /**
     * @throws Throwable
     */
    public function destroy(PermissionPreset $permissionPreset): void
    {
        $this->permissionPresetRepository->deleteOrFail($permissionPreset);
    }
}
