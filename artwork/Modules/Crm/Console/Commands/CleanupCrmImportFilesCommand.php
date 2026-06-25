<?php

namespace Artwork\Modules\Crm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupCrmImportFilesCommand extends Command
{
    protected $signature = 'crm:cleanup-import-files {--hours=24 : Delete files older than this many hours}';

    protected $description = 'Delete orphaned CRM import files from storage';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $directory = 'crm-imports';

        if (!$disk->exists($directory)) {
            $this->info('No crm-imports directory found. Nothing to clean up.');
            return self::SUCCESS;
        }

        $files = $disk->files($directory);
        $deleted = 0;

        // Nur tatsächlich verwaiste (alte) Dateien löschen – sonst werden gerade
        // hochgeladene Importe, deren Spalten-Mapping noch läuft, mitten im Workflow entfernt.
        $threshold = now()->subHours((int) $this->option('hours'))->getTimestamp();

        foreach ($files as $file) {
            if ($disk->lastModified($file) >= $threshold) {
                continue;
            }
            $disk->delete($file);
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphaned import file(s).");

        return self::SUCCESS;
    }
}
