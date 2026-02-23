<?php

namespace Artwork\Modules\Budget\Listeners;

use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Services\BudgetCacheService;
use Artwork\Modules\Project\Models\Project;

class InvalidateBudgetCache
{
    public function __construct(
        private readonly BudgetCacheService $budgetCacheService
    ) {
    }

    public function handle(BudgetUpdated $event): void
    {
        $this->budgetCacheService->forgetBudgetPayload($event->projectId);

        $project = Project::find($event->projectId);
        if ($project) {
            $this->budgetCacheService->forgetForProjectGroup($project);
        }
    }
}
