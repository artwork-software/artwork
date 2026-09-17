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

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Observer-Signatur (saved/deleted erhalten das Model)
    public function saved(Model $model): void
    {
        $this->budgetCacheService->forgetStaticLookups();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Observer-Signatur (saved/deleted erhalten das Model)
    public function deleted(Model $model): void
    {
        $this->budgetCacheService->forgetStaticLookups();
    }
}
