<?php

namespace Artwork\Modules\Project\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ProjectTab;

class ProjectTabRepository extends BaseRepository
{
    public function findFirstProjectTab(): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()->without(['components', 'sidebarTabs'])->first();

        return $projectTab;
    }

    public function findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum $type): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()
            ->byComponentsComponentType($type->value)
            ->first();

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

    public function getDefaultOrFirstProjectTab(): ?ProjectTab
    {
        return ProjectTab::query()
            ->without(['components', 'sidebarTabs'])
            ->where('default', true)
            ->first() ?? $this->findFirstProjectTab();
    }
}
