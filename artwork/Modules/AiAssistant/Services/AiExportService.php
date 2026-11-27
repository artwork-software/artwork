<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Core\FileHandling\Naming\DownloadFileName;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\AiAssistant\DTOs\AiExportData;
use Artwork\Modules\AiAssistant\Exports\AiTableExport;
use Artwork\Modules\AiAssistant\Models\AiExport;
use Artwork\Modules\AiAssistant\Support\AiExportCell;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Filesystem\FilesystemManager;
use Maatwebsite\Excel\Excel;

/**
 * Rendert eine Tabelle aus der KI-Bar als PDF (core-ai-event-stats-exports) — gleiche Technik wie die
 * Kalender-Exporte (Snappy/wkhtmltopdf, Blade unter resources/views/pdf, StoredFileName) — oder als
 * XLSX (maatwebsite/excel) bzw. CSV (core-ai-export-formats).
 *
 * CSV: UTF-8 mit BOM, Trenner `;`, Zeilenende CRLF — so oeffnet das deutsche Excel die Datei richtig.
 * Titel, Untertitel und Notiz stehen nur im PDF und (Titel/Untertitel) im XLSX, nicht in der CSV.
 *
 * Anders als dort wird die Datei nach dem Download nicht geloescht: der Link steht in der Historie
 * der Bar und soll bis zum Ablauf funktionieren. Aufraeumen macht ai:purge-exports.
 */
class AiExportService
{
    public const FORMATS = ['pdf', 'xlsx', 'csv'];

    public const MIME_TYPES = [
        'pdf' => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv; charset=UTF-8',
    ];

    public function __construct(
        private readonly PdfWrapper $pdf,
        private readonly FilesystemManager $files,
        private readonly Excel $excel,
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
        array $rows,
        string $format = 'pdf'
    ): AiExportData {
        $file = StoredFileName::forGenerated($format);
        $path = AiExport::DIRECTORY . '/' . $file;

        if ($this->files->directoryMissing(AiExport::DIRECTORY)) {
            $this->files->makeDirectory(AiExport::DIRECTORY);
        }

        $timezone = (string) config('app.timezone');

        match ($format) {
            'xlsx' => $this->excel->store(new AiTableExport($title, $subtitle, $columns, $rows), $path),
            'csv' => $this->files->put($path, self::csv($columns, $rows)),
            default => $this->pdf->loadView('pdf.ai-table', [
                'title' => $title,
                'subtitle' => $subtitle,
                'note' => $note,
                'columns' => $columns,
                'rows' => $rows,
                'createdAt' => now($timezone)->format('d.m.Y H:i'),
                'createdBy' => $user->full_name,
            ])
                ->setPaper('a4', count($columns) > 5 ? 'landscape' : 'portrait')
                ->save($this->files->path($path)),
        };

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
            filename: self::filename($title, $format),
            row_count: $export->row_count,
            expires_at: $export->expires_at->copy()->setTimezone($timezone)->toIso8601String(),
            format: $format,
            mime_type: self::MIME_TYPES[$format] ?? self::MIME_TYPES['pdf'],
        );
    }

    public static function filename(string $title, string $format = 'pdf'): string
    {
        return (DownloadFileName::sanitize($title) ?? 'artwork-export') . '.' . $format;
    }

    /**
     * @param list<string> $columns
     * @param list<list<string>> $rows
     */
    public static function csv(array $columns, array $rows): string
    {
        $line = static fn (array $cells): string => implode(';', array_map(
            static function (string $cell): string {
                $value = AiExportCell::safe($cell);

                return preg_match('/[;"\r\n]/', $value) === 1
                    ? '"' . str_replace('"', '""', $value) . '"'
                    : $value;
            },
            $cells
        )) . "\r\n";

        return "\u{FEFF}" . $line($columns) . implode('', array_map($line, $rows));
    }
}
