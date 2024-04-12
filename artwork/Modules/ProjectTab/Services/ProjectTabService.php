<?php

namespace Artwork\Modules\ProjectTab\Services;

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
        return $this->projectTabRepository->findFirstProjectTabWithShiftsComponent();
    }

    public function findFirstProjectTabWithTasksComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabWithTasksComponent();
    }

    public function findFirstProjectTabWithBudgetComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabWithBudgetComponent();
    }
}
