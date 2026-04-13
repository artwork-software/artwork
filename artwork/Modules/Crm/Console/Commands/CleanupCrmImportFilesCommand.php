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

        foreach ($files as $file) {
            $disk->delete($file);
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphaned import file(s).");

        return self::SUCCESS;
    }
}
