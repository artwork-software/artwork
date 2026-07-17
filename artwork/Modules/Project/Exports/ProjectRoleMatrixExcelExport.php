<?php

namespace Artwork\Modules\Project\Exports;

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

class ProjectRoleMatrixExcelExport implements FromView, WithEvents
{
    use Exportable;

    private const PROJECT_HEADER_ROW = 2;
    private const PERIOD_HEADER_ROW = 3;
    private const FIRST_DATA_ROW = 4;

    /**
     * @param array{
     *     projects: Collection<int, array{id: int, name: string, period_label: string}>,
     *     rows: Collection<int, array{name: string, roles: array<int, string>}>
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
        return view('exports.project-role-matrix', [
            'language' => $this->language,
            'periodLabel' => $this->rangeStart->format('d.m.Y') . ' – ' . $this->rangeEnd->format('d.m.Y'),
            'projects' => $this->matrix['projects'],
            'rows' => $this->matrix['rows'],
            'totalCols' => $this->totalCols(),
        ]);
    }

    private function totalCols(): int
    {
        return 1 + max($this->matrix['projects']->count(), 1);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $totalColumns = $this->totalCols();
                $lastCol = Coordinate::stringFromColumnIndex($totalColumns);
                $lastRow = self::PERIOD_HEADER_ROW + max($this->matrix['rows']->count(), 1);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

                $sheet->getStyle('A' . self::PROJECT_HEADER_ROW . ":{$lastCol}" . self::PERIOD_HEADER_ROW)
                    ->applyFromArray([
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

                $sheet->getStyle('A' . self::PROJECT_HEADER_ROW . ":{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D0D0D0'],
                        ],
                    ],
                ]);

                $sheet->getStyle('B' . self::FIRST_DATA_ROW . ":{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getColumnDimension('A')->setAutoSize(false);
                $sheet->getColumnDimension('A')->setWidth(26);
                for ($colIndex = 2; $colIndex <= $totalColumns; $colIndex++) {
                    $letter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($letter)->setAutoSize(false);
                    $sheet->getColumnDimension($letter)->setWidth(22);
                }

                $sheet->freezePane('B' . self::FIRST_DATA_ROW);
            },
        ];
    }
}
