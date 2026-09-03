<?php

namespace Artwork\Modules\Permission\Services;

use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Repositories\PermissionRepository;
use Artwork\Modules\Permission\Http\Requests\StorePermissionPresetRequest;
use Artwork\Modules\Permission\Http\Requests\UpdatePermissionPresetRequest;
use Artwork\Modules\Permission\Models\PermissionPreset;
use Artwork\Modules\Permission\Repositories\PermissionPresetRepository;
use Illuminate\Database\Eloquent\Collection;
use Artwork\Modules\Permission\Models\Permission;
use Throwable;

readonly class PermissionPresetService
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private PermissionPresetRepository $permissionPresetRepository,
        private PermissionCatalog $catalog,
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
                'permissions' => $this->normalizePermissions($storePermissionPresetRequest->get('permissions', [])),
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
                'permissions' => $this->normalizePermissions($updatePermissionPresetRequest->get('permissions', [])),
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

    /**
     * Presets speichern Rechte-NAMEN; Altbestände mit IDs werden aufgelöst. Implizierte Rechte
     * (Stufenleiter) werden ergänzt, damit ein Preset immer einen konsistenten Satz enthält.
     *
     * @param array<int, int|string> $permissions
     * @return string[]
     */
    public function normalizePermissions(array $permissions): array
    {
        $preset = new PermissionPreset(['permissions' => $permissions]);
        $names = $preset->permissionNames();
        $known = Permission::query()->whereIn('name', $this->catalog->expandWithImplied($names))->pluck('name')->all();

        return array_values(array_intersect($this->catalog->expandWithImplied($names), $known));
    }

    /** @return int Anzahl geänderter Presets */
    public function applyImplicationsToAll(): int
    {
        $touched = 0;
        foreach (PermissionPreset::query()->get() as $preset) {
            $normalized = $this->normalizePermissions($preset->permissions ?? []);
            $current = array_values(array_unique(array_map('strval', $preset->permissions ?? [])));
            sort($normalized);
            sort($current);
            if ($normalized === $current) {
                continue;
            }
            $preset->update(['permissions' => $normalized]);
            $touched++;
        }

        return $touched;
    }
}
