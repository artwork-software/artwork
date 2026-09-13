<?php

namespace Artwork\Modules\BusinessIntelligence\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ein Tabellenblatt des BI-Exports. FromArray (statt FromView): PHP-Typen kommen
 * unverändert in Excel an — Zahlen bleiben Zahlen, bool wird TRUE/FALSE, Datums-
 * Serienwerte werden über columnFormats() als echte Datumszellen dargestellt.
 * Kopfzeile fixiert + Autofilter, damit die Datei ohne Nacharbeit pivotierbar ist.
 */
class BiProjectExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting,
    WithTitle,
    WithEvents,
    // Ohne strikten Vergleich würden false und 0 als leere Zellen ausgelassen
    WithStrictNullComparison
{
    use Exportable;

    public const FORMAT_CURRENCY = '#,##0.00 "€"';
    public const FORMAT_PERCENT = '0.0%';
    public const FORMAT_COUNT = '#,##0';
    public const FORMAT_DATE = 'DD.MM.YYYY';
    public const FORMAT_DATETIME = 'DD.MM.YYYY HH:MM';

    /**
     * Excel-Anzeigeformate je Spaltenschlüssel. Prozentwerte werden als Anteil
     * (0–1) exportiert, damit Excel-Prozentformat und Weiterrechnen stimmen.
     */
    private const COLUMN_FORMATS = [
        'revenue' => self::FORMAT_CURRENCY,
        'avg_price' => self::FORMAT_CURRENCY,
        'plan_revenue' => self::FORMAT_CURRENCY,
        'occupancy_rate' => self::FORMAT_PERCENT,
        'free_tickets_rate' => self::FORMAT_PERCENT,
        'reduced_tickets_rate' => self::FORMAT_PERCENT,
        'paying_rate' => self::FORMAT_PERCENT,
        'no_show_rate' => self::FORMAT_PERCENT,
        'seat_occupancy' => self::FORMAT_PERCENT,
        'attainment' => self::FORMAT_PERCENT,
        'visitors' => self::FORMAT_COUNT,
        'sold_tickets' => self::FORMAT_COUNT,
        'seats_capacity' => self::FORMAT_COUNT,
        'tickets_issued' => self::FORMAT_COUNT,
        'plan_visitors' => self::FORMAT_COUNT,
        'plan_sold_tickets' => self::FORMAT_COUNT,
        'contract_count' => self::FORMAT_COUNT,
        'event_count' => self::FORMAT_COUNT,
        'booking_count' => self::FORMAT_COUNT,
        'task_total' => self::FORMAT_COUNT,
        'task_open' => self::FORMAT_COUNT,
        'task_done' => self::FORMAT_COUNT,
        'document_count' => self::FORMAT_COUNT,
        'department_count' => self::FORMAT_COUNT,
        'user_count' => self::FORMAT_COUNT,
        'tasks_docs_per_production' => self::FORMAT_COUNT,
        'first_event_date' => self::FORMAT_DATE,
        'premiere_date' => self::FORMAT_DATE,
        'event_date' => self::FORMAT_DATE,
        'event_start' => self::FORMAT_DATETIME,
        'event_end' => self::FORMAT_DATETIME,
    ];

    public function __construct(
        private readonly array $rows,
        private readonly array $columns,
        private readonly array $labels = [],
        private readonly string $title = 'Export',
        // Zusätzliche/dynamische Formate (Spaltenkey => Excel-Format), z. B. für
        // die variablen Wertspalten des Budget-Exports; überschreiben die Defaults
        private readonly array $extraColumnFormats = [],
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_map(
            fn (string $column): string => (string) ($this->labels[$column] ?? __($column)),
            array_values($this->columns)
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return array_map(
            fn (array $row): array => array_map(
                fn (string $column): mixed => $row[$column] ?? '',
                array_values($this->columns)
            ),
            $this->rows
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $formats = [];
        $formatMap = array_merge(self::COLUMN_FORMATS, $this->extraColumnFormats);

        foreach (array_values($this->columns) as $index => $column) {
            if (isset($formatMap[$column])) {
                $formats[Coordinate::stringFromColumnIndex($index + 1)] = $formatMap[$column];
            }
        }

        return $formats;
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => static function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                // Kopfzeile bleibt beim Scrollen stehen, Filter direkt nutzbar
                $sheet->freezePane('A2');
                if ($sheet->getHighestRow() > 1) {
                    $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                }
            },
        ];
    }
}
