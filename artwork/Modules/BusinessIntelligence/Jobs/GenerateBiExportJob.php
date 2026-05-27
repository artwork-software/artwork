<?php

namespace Artwork\Modules\BusinessIntelligence\Jobs;

use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateBiExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $token)
    {
    }

    public function handle(BiExportService $biExportService): void
    {
        $biExportService->generateAndStore($this->token);
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put(
            'bi_export_status_' . $this->token,
            ['status' => 'failed', 'message' => $exception->getMessage()],
            now()->addMinutes(30)
        );
    }
}
