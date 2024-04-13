<?php

namespace Artwork\Modules\ProjectTab\Repositories;

use App\Enums\TabComponentEnums;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ProjectTab\Models\ProjectTab;

class ProjectTabRepository extends BaseRepository
{
    public function findFirstProjectTab(): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()->first();

        return $projectTab;
    }

    public function findFirstProjectTabByComponentsComponentType(TabComponentEnums $type): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()
            ->byComponentsComponentType($type->value)
            ->first();

        return $projectTab;
    }
}
