<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Core\FileHandling\Naming\DownloadFileName;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\AiAssistant\DTOs\AiExportData;
use Artwork\Modules\AiAssistant\Models\AiExport;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Filesystem\FilesystemManager;

/**
 * Rendert eine Tabelle aus der KI-Bar als PDF (core-ai-event-stats-exports) — gleiche Technik wie die
 * Kalender-Exporte (Snappy/wkhtmltopdf, Blade unter resources/views/pdf, StoredFileName).
 *
 * Anders als dort wird die Datei nach dem Download nicht geloescht: der Link steht in der Historie
 * der Bar und soll bis zum Ablauf funktionieren. Aufraeumen macht ai:purge-exports.
 */
class AiExportService
{
    public function __construct(
        private readonly PdfWrapper $pdf,
        private readonly FilesystemManager $files,
    ) {
    }

    /**
     * @param list<string> $columns
     * @param list<list<string>> $rows
     */
    public function create(
        User $user,
        string $title,
        ?string $subtitle,
        ?string $note,
        array $columns,
        array $rows
    ): AiExportData {
        $file = StoredFileName::forGenerated('pdf');

        if ($this->files->directoryMissing(AiExport::DIRECTORY)) {
            $this->files->makeDirectory(AiExport::DIRECTORY);
        }

        $timezone = (string) config('app.timezone');

        $this->pdf->loadView('pdf.ai-table', [
            'title' => $title,
            'subtitle' => $subtitle,
            'note' => $note,
            'columns' => $columns,
            'rows' => $rows,
            'createdAt' => now($timezone)->format('d.m.Y H:i'),
            'createdBy' => $user->full_name,
        ])
            ->setPaper('a4', count($columns) > 5 ? 'landscape' : 'portrait')
            ->save($this->files->path(AiExport::DIRECTORY . '/' . $file));

        $export = AiExport::query()->create([
            'user_id' => $user->id,
            'file' => $file,
            'title' => $title,
            'row_count' => count($rows),
            'expires_at' => now()->addDays((int) config('ai.export_retention_days')),
        ]);

        return new AiExportData(
            id: $export->id,
            url: route('ai.exports.download', ['export' => $export->id], false),
            filename: self::filename($title),
            row_count: $export->row_count,
            expires_at: $export->expires_at->copy()->setTimezone($timezone)->toIso8601String(),
        );
    }

    public static function filename(string $title): string
    {
        return (DownloadFileName::sanitize($title) ?? 'artwork-export') . '.pdf';
    }
}
