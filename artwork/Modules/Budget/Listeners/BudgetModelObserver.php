<?php

namespace Artwork\Modules\Budget\Listeners;

use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetCacheService;
use Artwork\Modules\Budget\Services\BudgetModelProjectResolverService;
use Illuminate\Database\Eloquent\Model;

class BudgetModelObserver
{
    public function __construct(
        private readonly BudgetModelProjectResolverService $projectResolverService,
        private readonly BudgetCacheService $budgetCacheService,
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
        // Template-Tabellen haben keine project_id - deren Aenderungen
        // invalidieren stattdessen den Templates-Cache.
        if ($model instanceof Table && $model->is_template) {
            $this->budgetCacheService->forgetTemplates();
            return;
        }

        $projectId = $this->projectResolverService->resolveProjectId($model);

        if ($projectId) {
            BudgetUpdated::dispatch($projectId);
        }
    }
}
