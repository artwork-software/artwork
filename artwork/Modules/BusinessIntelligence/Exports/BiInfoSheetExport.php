<?php

namespace Artwork\Modules\BusinessIntelligence\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Blatt "Info": Herkunft der Datei (Zeitraum, Filter, Ersteller, Zeitstempel),
 * damit ein Export auch Wochen später noch nachvollziehbar ist.
 */
class BiInfoSheetExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    use Exportable;

    /**
     * @param array<int, array{0: string, 1: mixed}> $entries Label/Wert-Paare
     */
    public function __construct(
        private readonly array $entries,
        private readonly string $title = 'Info'
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return array_map(
            static fn (array $entry): array => [$entry[0], $entry[1]],
            $this->entries
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            'A' => ['font' => ['bold' => true]],
        ];
    }
}
