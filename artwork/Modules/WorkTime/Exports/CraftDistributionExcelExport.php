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

class CraftDistributionExcelExport implements FromView, WithEvents
{
    use Exportable;

    private const CRAFT_HEADER_ROW = 2;
    private const SUB_HEADER_ROW = 3;
    private const FIRST_DATA_ROW = 4;
    private const COLS_PER_CRAFT = 2;

    /**
     * @param array{
     *     universalCraft: array{id: int, name: string},
     *     crafts: Collection<int, array{id: int, name: string}>,
     *     rows: Collection<int, array{name: string, minutes: array<int, int>, other_minutes: int, total_minutes: int}>,
     *     total: array{minutes: array<int, int>, other_minutes: int, total_minutes: int}
     * } $distribution
     */
    public function __construct(
        private readonly array $distribution,
        private readonly Carbon $rangeStart,
        private readonly Carbon $rangeEnd,
        private readonly string $language,
    ) {
    }

    public function view(): View
    {
        return view('exports.craft-distribution', [
            'language' => $this->language,
            'universalCraftName' => $this->distribution['universalCraft']['name'],
            'periodLabel' => $this->rangeStart->format('d.m.Y') . ' – ' . $this->rangeEnd->format('d.m.Y'),
            'crafts' => $this->distribution['crafts'],
            'rows' => $this->distribution['rows']->map(
                fn (array $row) => $this->presentRow($row['name'], $row),
            ),
            'totalRow' => $this->presentRow(__('Total', [], $this->language), $this->distribution['total']),
            'totalCols' => $this->totalCols(),
        ]);
    }

    private function totalCols(): int
    {
        // name column + (crafts + "other" bucket) * (hours, share) + total hours column
        return 1 + ($this->distribution['crafts']->count() + 1) * self::COLS_PER_CRAFT + 1;
    }

    /**
     * @param array{minutes: array<int, int>, other_minutes: int, total_minutes: int} $row
     * @return array{label: string, values: array<int, float>, total_hours: float}
     */
    private function presentRow(string $label, array $row): array
    {
        $values = [];

        foreach ($this->distribution['crafts'] as $craft) {
            $minutes = $row['minutes'][$craft['id']] ?? 0;
            $values[] = $this->minutesToHours($minutes);
            $values[] = $this->share($minutes, $row['total_minutes']);
        }

        $values[] = $this->minutesToHours($row['other_minutes']);
        $values[] = $this->share($row['other_minutes'], $row['total_minutes']);

        return [
            'label' => $label,
            'values' => $values,
            'total_hours' => $this->minutesToHours($row['total_minutes']),
        ];
    }

    private function minutesToHours(int $minutes): float
    {
        return round($minutes / 60, 2);
    }

    private function share(int $minutes, int $totalMinutes): float
    {
        return $totalMinutes > 0 ? round($minutes / $totalMinutes, 4) : 0.0;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $totalColumns = $this->totalCols();
                $lastCol = Coordinate::stringFromColumnIndex($totalColumns);
                $lastRow = self::SUB_HEADER_ROW + $this->distribution['rows']->count() + 1;

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

                $craftGroupCount = $this->distribution['crafts']->count() + 1;
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

                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA'],
                    ],
                ]);

                // alternate hours (0.00) and share (0.0%) columns per craft group
                for ($group = 0; $group < $craftGroupCount; $group++) {
                    $hoursCol = Coordinate::stringFromColumnIndex(2 + $group * self::COLS_PER_CRAFT);
                    $shareCol = Coordinate::stringFromColumnIndex(3 + $group * self::COLS_PER_CRAFT);
                    $sheet->getStyle("{$hoursCol}" . self::FIRST_DATA_ROW . ":{$hoursCol}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                    $sheet->getStyle("{$shareCol}" . self::FIRST_DATA_ROW . ":{$shareCol}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('0.0%');
                }

                $sheet->getStyle("{$lastCol}" . self::FIRST_DATA_ROW . ":{$lastCol}{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                $sheet->getColumnDimension('A')->setAutoSize(false);
                $sheet->getColumnDimension('A')->setWidth(24);
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
