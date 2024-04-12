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

    public function findFirstProjectTabWithShiftsComponent(): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()
            ->byComponentsComponentType(TabComponentEnums::SHIFT_TAB->value)
            ->first();

        return $projectTab;
    }

    public function findFirstProjectTabWithTasksComponent(): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()
            ->byComponentsComponentType(TabComponentEnums::CHECKLIST->value)
            ->first();

        return $projectTab;
    }

    public function findFirstProjectTabWithBudgetComponent(): ProjectTab|null
    {
        /** @var ProjectTab $projectTab */
        $projectTab = ProjectTab::query()
            ->byComponentsComponentType(TabComponentEnums::BUDGET->value)
            ->first();

        return $projectTab;
    }
}
