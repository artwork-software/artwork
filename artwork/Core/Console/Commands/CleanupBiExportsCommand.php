<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Illuminate\Console\Command;

/**
 * Exportdateien bleiben nach dem Download liegen (Re-Download möglich) und werden
 * hier nach Ablauf entfernt. Läuft täglich über den Scheduler.
 */
class CleanupBiExportsCommand extends Command
{
    protected $signature = 'artwork:bi-exports:cleanup {--hours=24 : Dateien älter als diese Stundenzahl löschen}';
    protected $description = 'Delete stored BI export files older than the given number of hours';

    public function handle(BiExportService $biExportService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $deleted = $biExportService->cleanupStoredExports($hours);

        $this->info(sprintf('Deleted %d BI export file(s) older than %d hour(s).', $deleted, $hours));

        return self::SUCCESS;
    }
}
