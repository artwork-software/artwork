<?php

namespace Artwork\Modules\WorkTime\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class WorkTimeOverviewExcelExport implements FromView, WithEvents
{
    use Exportable;

    private const CRAFT_HEADER_ROW = 2;
    private const SUB_HEADER_ROW = 3;
    private const FIRST_DATA_ROW = 4;
    private const COLS_PER_CRAFT = 4;

    /**
     * @param array{
     *     crafts: Collection<int, array{id: int, name: string}>,
     *     rows: Collection<int, array<string, mixed>>
     * } $matrix
     */
    public function __construct(
        private readonly array $matrix,
        private readonly Carbon $rangeStart,
        private readonly Carbon $rangeEnd,
        private readonly string $language,
    ) {
    }

    public function view(): View
    {
        return view('exports.work-time-overview', [
            'language' => $this->language,
            'periodLabel' => $this->rangeStart->format('m/Y') . ' – ' . $this->rangeEnd->format('m/Y'),
            'crafts' => $this->matrix['crafts'],
            'rows' => $this->matrix['rows']->map(fn (array $row) => $this->presentRow($row)),
            'totalCols' => $this->totalCols(),
        ]);
    }

    private function totalCols(): int
    {
        return 1 + ($this->matrix['crafts']->count() + 1) * self::COLS_PER_CRAFT;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{label: string, values: array<int, float>}
     */
    private function presentRow(array $row): array
    {
        $values = [];

        foreach ($this->matrix['crafts'] as $craft) {
            $cell = $row['cells'][$craft['id']] ?? null;
            $values[] = $this->minutesToHours($cell['soll_intern'] ?? 0);
            $values[] = $this->minutesToHours($cell['ist_intern'] ?? 0);
            $values[] = $this->minutesToHours($cell['soll_extern'] ?? 0);
            $values[] = $this->minutesToHours($cell['ist_extern'] ?? 0);
        }

        $values[] = $this->minutesToHours($row['total']['soll_intern']);
        $values[] = $this->minutesToHours($row['total']['ist_intern']);
        $values[] = $this->minutesToHours($row['total']['soll_extern']);
        $values[] = $this->minutesToHours($row['total']['ist_extern']);

        return [
            'label' => $row['is_sum']
                ? __('Total', [], $this->language) . ' ' . $row['label']
                : $row['label'],
            'values' => $values,
        ];
    }

    private function minutesToHours(int $minutes): float
    {
        return round($minutes / 60, 2);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $totalColumns = $this->totalCols();
                $lastCol = Coordinate::stringFromColumnIndex($totalColumns);
                $lastRow = self::SUB_HEADER_ROW + $this->matrix['rows']->count();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

                $sheet->getStyle('A' . self::CRAFT_HEADER_ROW . ":{$lastCol}" . self::SUB_HEADER_ROW)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle('A' . self::CRAFT_HEADER_ROW . ":{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D0D0D0'],
                        ],
                    ],
                ]);

                $craftGroupCount = $this->matrix['crafts']->count() + 1;
                for ($group = 0; $group <= $craftGroupCount; $group++) {
                    $colIndex = 1 + $group * self::COLS_PER_CRAFT;
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet
                        ->getStyle("{$colLetter}" . self::CRAFT_HEADER_ROW . ":{$colLetter}{$lastRow}")
                        ->applyFromArray([
                            'borders' => [
                                'right' => [
                                    'borderStyle' => Border::BORDER_MEDIUM,
                                    'color' => ['rgb' => 'A6A6A6'],
                                ],
                            ],
                        ]);
                }

                foreach ($this->matrix['rows']->values() as $index => $row) {
                    if (!$row['is_sum']) {
                        continue;
                    }

                    $rowNumber = self::FIRST_DATA_ROW + $index;
                    $sheet->getStyle("A{$rowNumber}:{$lastCol}{$rowNumber}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E2EFDA'],
                        ],
                    ]);
                }

                $sheet->getStyle('B' . self::FIRST_DATA_ROW . ":{$lastCol}{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                $sheet->getColumnDimension('A')->setAutoSize(false);
                $sheet->getColumnDimension('A')->setWidth(18);
                for ($colIndex = 2; $colIndex <= $totalColumns; $colIndex++) {
                    $letter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($letter)->setAutoSize(false);
                    $sheet->getColumnDimension($letter)->setWidth(11);
                }

                $sheet->freezePane('B' . self::FIRST_DATA_ROW);
            },
        ];
    }
}
