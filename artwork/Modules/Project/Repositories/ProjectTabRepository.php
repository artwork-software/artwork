<?php

namespace Artwork\Modules\Project\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;

class ProjectTabRepository extends BaseRepository
{
    public function findFirstProjectTab(?User $user = null): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $query = ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->orderBy('order');

        if ($user) {
            $query->visibleForUser($user);
        } else {
            $query->where('visible_for_all', true);
        }

        $projectTab = $query->first();

        return $projectTab;
    }

    public function findFirstProjectTabByComponentsComponentType(
        ProjectTabComponentEnum $type,
        ?User $user = null
    ): ProjectTab|null {
        /** @var ProjectTab $projectTab */
        $query = ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->byComponentsComponentType($type->value)
            ->orderBy('order');

        if ($user) {
            $query->visibleForUser($user);
        } else {
            $query->where('visible_for_all', true);
        }

        $projectTab = $query->first();

        return $projectTab;
    }

    public function findByName(string $name): ?ProjectTab
    {
        return ProjectTab::query()
            ->where('name', $name)
            ->first();
    }

    public function findById(int $id): ?ProjectTab
    {
        return ProjectTab::find($id);
    }

    public function getDefaultOrFirstProjectTab(?User $user = null): ?ProjectTab
    {
        $query = ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->where('default', true)
            ->orderBy('order');

        if ($user) {
            $query->visibleForUser($user);
        } else {
            $query->where('visible_for_all', true);
        }

        return $query->first() ?? $this->findFirstProjectTab($user);
    }
}
