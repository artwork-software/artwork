<?php

namespace Artwork\Modules\Budget\Listeners;

use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Jobs\WarmBudgetCache;
use Artwork\Modules\Budget\Services\BudgetCacheService;

class InvalidateBudgetCache
{
    public function __construct(
        private readonly BudgetCacheService $budgetCacheService
    ) {
    }

    public function handle(BudgetUpdated $event): void
    {
        // Memoisiert die Gruppen-Aufloesung pro Projekt, damit Mutations-Bursts
        // (Spalte anlegen, Sage-Import) nicht pro Save erneut Gruppen-Queries
        // bezahlen. Das eigentliche Cache-Forget bleibt pro Save (idempotent).
        $this->budgetCacheService->forgetForProjectAndRelated($event->projectId);

        // Cache asynchron wieder aufwärmen, damit der nächste Abruf (u. a. der
        // sofortige Refetch des Budget-Tabs) nicht den teuren Neuaufbau bezahlt.
        // Pro Request/Prozess nur einmal dispatchen.
        if ($this->budgetCacheService->shouldDispatchWarmJob($event->projectId)) {
            WarmBudgetCache::dispatch($event->projectId);
        }
    }
}
