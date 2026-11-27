<?php

namespace Artwork\Modules\AiAssistant\Console\Commands;

use Artwork\Modules\AiAssistant\Models\AiExport;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;

/**
 * Loescht abgelaufene KI-Exporte samt Datei (core-ai-event-stats-exports).
 */
class PurgeExportsCommand extends Command
{
    protected $signature = 'ai:purge-exports';

    protected $description = 'Loescht abgelaufene PDF-Exporte der KI-Bar.';

    public function handle(FilesystemManager $files): int
    {
        $deleted = 0;

        AiExport::query()
            ->where('expires_at', '<=', now())
            ->chunkById(200, function ($exports) use ($files, &$deleted): void {
                foreach ($exports as $export) {
                    $files->delete($export->storagePath());
                    $export->delete();
                    $deleted++;
                }
            });

        $this->info(sprintf('%d Export(e) geloescht.', $deleted));

        return self::SUCCESS;
    }
}
