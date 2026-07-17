<?php

namespace Artwork\Modules\BusinessIntelligence\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BiProjectExport implements FromView, ShouldAutoSize, WithStyles, WithColumnFormatting, WithTitle
{
    use Exportable;

    /**
     * Excel-Anzeigeformate für Zahlenspalten. Die Werte kommen als Rohzahlen aus
     * dem Service (Punkt als Dezimaltrenner), Excel rendert sie locale-korrekt.
     */
    private const COLUMN_FORMATS = [
        'revenue' => '#,##0.00 "€"',
        'avg_price' => '#,##0.00 "€"',
        'occupancy_rate' => '0.0" %"',
    ];

    public function __construct(
        private readonly array $rows,
        private readonly array $columns,
        private readonly array $labels = [],
        private readonly string $title = 'Export',
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function view(): View
    {
        return view('exports.biProjects', [
            'rows' => $this->rows,
            'columns' => $this->columns,
            'labels' => $this->labels,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $formats = [];

        foreach (array_values($this->columns) as $index => $column) {
            if (isset(self::COLUMN_FORMATS[$column])) {
                $formats[Coordinate::stringFromColumnIndex($index + 1)] = self::COLUMN_FORMATS[$column];
            }
        }

        return $formats;
    }
}
