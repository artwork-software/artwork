<?php

namespace Artwork\Modules\ProjectTab\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Models\ProjectTab;

readonly class ProjectTabRepository extends BaseRepository
{
    public function findFirstProjectTab(): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()->first();

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
}
