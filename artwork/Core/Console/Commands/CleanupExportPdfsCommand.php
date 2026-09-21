<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Export-PDFs unter storage/app/pdf werden normalerweise per deleteFileAfterSend entfernt; bricht der
 * Download ab, bleiben sie liegen und werden hier nach 24 h aufgeräumt. Läuft täglich über den Scheduler.
 */
class CleanupExportPdfsCommand extends Command
{
    protected $signature = 'artwork:cleanup:export-pdfs {--hours=24 : Dateien älter als diese Stundenzahl löschen}';

    protected $description = 'Delete generated export PDFs (storage/app/pdf) older than the given number of hours';

    /**
     * @var list<string>
     */
    private const DIRECTORIES = ['pdf'];

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $threshold = now()->subHours($hours)->getTimestamp();
        $disk = Storage::disk('local');
        $deleted = 0;

        foreach (self::DIRECTORIES as $directory) {
            if (!$disk->exists($directory)) {
                continue;
            }

            foreach ($disk->allFiles($directory) as $file) {
                if (!str_ends_with(strtolower($file), '.pdf')) {
                    continue;
                }

                if ($disk->lastModified($file) < $threshold) {
                    $disk->delete($file);
                    $deleted++;
                }
            }
        }

        $this->info(sprintf('Deleted %d export PDF(s) older than %d hour(s).', $deleted, $hours));

        return self::SUCCESS;
    }
}
