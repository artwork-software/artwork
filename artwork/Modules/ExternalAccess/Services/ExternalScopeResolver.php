<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Project\Models\ComponentInTab;
use Illuminate\Support\Collection;

class ExternalScopeResolver
{
    /**
     * Checks whether an ExternalAccess currently has an active scope on a
     * specific tab. Returns the matching scope, otherwise null.
     */
    public function resolveScope(
        ExternalAccess $external,
        int $projectId,
        int $projectTabId
    ): ?ExternalAccessScope {
        return $external->scopes()
            ->where('project_id', $projectId)
            ->where('project_tab_id', $projectTabId)
            ->currentlyValid()
            ->first();
    }

    /**
     * Returns all currently active scopes of an ExternalAccess.
     *
     * @return Collection<int, ExternalAccessScope>
     */
    public function activeScopesFor(ExternalAccess $external): Collection
    {
        return $external->scopes()
            ->currentlyValid()
            ->with(['project:id,name', 'projectTab:id,name'])
            ->get();
    }

    /**
     * Checks whether the ExternalAccess has write access to the tab.
     */
    public function canWrite(
        ExternalAccess $external,
        int $projectId,
        int $projectTabId
    ): bool {
        $scope = $this->resolveScope($external, $projectId, $projectTabId);

        return $scope !== null && $scope->access_type === ExternalAccessType::WRITE;
    }

    /**
     * Checks whether a concrete component lives inside the shared tab.
     * Defense in depth against cross-tab manipulation: external has a scope on
     * tab A but tries to write a component that belongs to tab B.
     */
    public function componentBelongsToTab(int $componentId, int $projectTabId): bool
    {
        return ComponentInTab::query()
            ->where('component_id', $componentId)
            ->where('project_tab_id', $projectTabId)
            ->exists();
    }
}
