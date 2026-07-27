<?php

namespace Artwork\Modules\Budget\Jobs;

use Artwork\Modules\Budget\Services\BudgetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Baut den Budget-Payload-Cache eines Projekts nach einer Invalidierung
 * asynchron neu auf, damit der nächste Abruf (z. B. der sofortige Refetch des
 * Budget-Tabs) auf einen warmen Cache trifft statt den teuren Aufbau im
 * Request zu bezahlen. ShouldBeUnique verhindert doppelte Rebuilds bei
 * Mutations-Bursts.
 */
class WarmBudgetCache implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor = 10;

    public function __construct(
        public readonly int $projectId
    ) {
    }

    public function uniqueId(): string
    {
        return (string) $this->projectId;
    }

    public function handle(BudgetService $budgetService): void
    {
        $budgetService->warmBudgetCache($this->projectId);
    }
}
