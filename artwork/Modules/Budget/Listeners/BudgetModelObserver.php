<?php

namespace Artwork\Modules\Budget\Listeners;

use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Services\BudgetModelProjectResolverService;
use Illuminate\Database\Eloquent\Model;

class BudgetModelObserver
{
    public function __construct(
        private readonly BudgetModelProjectResolverService $projectResolverService,
    ) {
    }

    public function saved(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    private function invalidateForModel(Model $model): void
    {
        $projectId = $this->projectResolverService->resolveProjectId($model);

        if ($projectId) {
            BudgetUpdated::dispatch($projectId);
        }
    }
}
