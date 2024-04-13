<?php

namespace Artwork\Modules\ProjectTab\Services;

use App\Enums\TabComponentEnums;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Repositories\ProjectTabRepository;

class ProjectTabService
{
    public function __construct(private readonly ProjectTabRepository $projectTabRepository)
    {
    }

    public function findFirstProjectTab(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTab();
    }

    public function findFirstProjectTabWithShiftsComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::SHIFT_TAB);
    }

    public function findFirstProjectTabWithTasksComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::CHECKLIST);
    }

    public function findFirstProjectTabWithBudgetComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::BUDGET);
    }

    public function findFirstProjectTabWithCalendarComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::CALENDAR);
    }
}
