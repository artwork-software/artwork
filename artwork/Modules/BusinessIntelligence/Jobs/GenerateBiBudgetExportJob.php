<?php

namespace Artwork\Modules\BusinessIntelligence\Jobs;

use Artwork\Modules\BusinessIntelligence\Services\BiBudgetExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateBiBudgetExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // Der Export läuft über alle Budget-Zeilen der getroffenen Projekte —
    // bei großen Häusern deutlich mehr als die Worker-Default-60s.
    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(private readonly string $token)
    {
    }

    public function handle(BiBudgetExportService $biBudgetExportService): void
    {
        $biBudgetExportService->generateAndStore($this->token);
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put(
            'bi_budget_export_status_' . $this->token,
            ['status' => 'failed', 'message' => $exception->getMessage()],
            now()->addMinutes(30)
        );
    }
}
