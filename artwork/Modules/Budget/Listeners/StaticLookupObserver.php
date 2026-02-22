<?php

namespace Artwork\Modules\Budget\Listeners;

use Artwork\Modules\Budget\Services\BudgetCacheService;
use Illuminate\Database\Eloquent\Model;

class StaticLookupObserver
{
    public function __construct(
        private readonly BudgetCacheService $budgetCacheService
    ) {
    }

    public function saved(Model $model): void
    {
        $this->budgetCacheService->forgetStaticLookups();
    }

    public function deleted(Model $model): void
    {
        $this->budgetCacheService->forgetStaticLookups();
    }
}
