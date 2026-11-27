<?php

namespace Artwork\Modules\AiAssistant\Exports;

use Artwork\Modules\AiAssistant\Support\AiExportCell;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Tabelle aus der KI-Bar als Excel-Datei (core-ai-export-formats): Titel und Untertitel oben, darunter
 * Kopfzeile und Zeilen.
 *
 * Zellen sind Text wie im Contract. Reine Zahlen (`6500.00`, `-322`) werden als Zahl geschrieben, damit
 * Summen in Excel funktionieren; alles andere — auch Datumsangaben und Werte, die wie Formeln aussehen —
 * ausdruecklich als Text. Damit wird nie eine Formel aus einem Zellinhalt.
 */
class AiTableExport extends DefaultValueBinder implements
    FromArray,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithCustomValueBinder
{
    /**
     * @param list<string> $columns
     * @param list<list<string>> $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly ?string $subtitle,
        private readonly array $columns,
        private readonly array $rows,
    ) {
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [...$this->preamble(), $this->columns, ...$this->rows];
    }

    public function title(): string
    {
        // Blattnamen: hoechstens 31 Zeichen, ohne : \ / ? * [ ]
        $name = trim((string) preg_replace('/[:\\\\\/?*\[\]]/', ' ', $this->title));

        return mb_substr($name === '' ? 'Export' : $name, 0, 31);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            count($this->preamble()) + 1 => ['font' => ['bold' => true]],
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $text = (string) $value;

        if ($text !== '' && AiExportCell::isNumber($text)) {
            $cell->setValueExplicit(str_contains($text, '.') ? (float) $text : (int) $text, DataType::TYPE_NUMERIC);

            return true;
        }

        $cell->setValueExplicit($text, DataType::TYPE_STRING);

        return true;
    }

    /**
     * Titel, optional Untertitel, dann eine Leerzeile.
     *
     * @return list<list<string>>
     */
    private function preamble(): array
    {
        return $this->subtitle === null || $this->subtitle === ''
            ? [[$this->title], []]
            : [[$this->title], [$this->subtitle], []];
    }
}
