<?php

namespace Artwork\Modules\Shift\Exports;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectShiftPersonalPlanExcelExport implements FromView, WithEvents, WithColumnFormatting
{
    use Exportable;

    private Collection $qualColumns;

    private int $rowCount = 0;
    private int $totalCols = 26;
    private string $lastCol = 'Z';

    public function __construct(
        protected Project $project,
        public string $language = 'de'
    ) {
    }

    public function view(): View
    {
        $shifts = $this->project->shifts()
            ->with(['craft', 'room', 'users', 'freelancer', 'serviceProvider'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->get();

        $this->rowCount = $shifts->count();
        $this->qualColumns = $this->resolveProjectQualificationColumns($shifts);

        $n = $this->qualColumns->count();

        $this->totalCols = 7 + (2 * $n + 3) + (2 * $n + 3) + 1;
        $this->lastCol = Coordinate::stringFromColumnIndex($this->totalCols);

        $rows = $shifts->map(fn (Shift $shift) => $this->buildRow($shift, $this->qualColumns));

        [$periodStart, $periodEnd] = $this->computePeriod($shifts);

        $totalMinutes = (int) $rows->sum('minutes_total');

        return view('exports.project-shifts-personal-plan', [
            'project'           => $this->project,
            'user'              => auth()->user(),
            'language'          => $this->language,

            'periodLabel'       => ($periodStart && $periodEnd)
                ? ($periodStart->format('d.m.Y') . ' – ' . $periodEnd->format('d.m.Y'))
                : '',

            'quals'             => $this->qualColumns,
            'rows'              => $rows,

            'totalHeadcount'    => (int) $rows->sum('total_headcount'),
            'totalMinutesLabel' => $this->formatMinutesLabel($totalMinutes),

            'lastCol'           => $this->lastCol,
            'totalCols'         => $this->totalCols,
        ]);
    }

    /**
     * @param Collection<int, Shift> $shifts
     * @return Collection<int, ShiftQualification>
     */
    private function resolveProjectQualificationColumns(Collection $shifts): Collection
    {
        $ids = collect();

        foreach ($shifts as $shift) {
            foreach ($shift->users as $u) {
                $qid = (int)($u->pivot?->shift_qualification_id ?? 0);
                if ($qid > 0) {
                    $ids->push($qid);
                }
            }
            foreach ($shift->freelancer as $f) {
                $qid = (int)($f->pivot?->shift_qualification_id ?? 0);
                if ($qid > 0) {
                    $ids->push($qid);
                }
            }
            foreach ($shift->serviceProvider as $s) {
                $qid = (int)($s->pivot?->shift_qualification_id ?? 0);
                if ($qid > 0) {
                    $ids->push($qid);
                }
            }
        }

        $ids = $ids->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $q = ShiftQualification::query()->whereIn('id', $ids->all());

        if (method_exists(ShiftQualification::class, 'scopeAvailable')) {
            $q->available();
        }

        if (method_exists(ShiftQualification::class, 'scopeOrderByCreationDateAscending')) {
            $q->orderByCreationDateAscending();
        }

        // absolute Stabilität
        $q->orderBy('name');

        return $q->get(['id', 'name']);
    }

    /**
     * @param Collection<int, ShiftQualification> $quals
     */
    private function buildRow(Shift $shift, Collection $quals): array
    {
        $dateLabel  = $this->formatShiftDate($shift->start_date, $this->language);
        $roomName   = (string) optional($shift->room)->name;

        $startLabel = $this->formatShiftTime($shift->start);
        $endLabel   = $this->formatShiftTime($shift->end);

        [$durationMinutes] = $this->duration($shift->start, $shift->end);

        $breakMinutes = (int)($shift->break_minutes ?? 0);
        $breakLabel   = $breakMinutes > 0 ? ($breakMinutes . ' min') : '0 min';

        $intCounts = $this->countByQualifications($shift->users, $quals);
        $extWorkers = $shift->freelancer->concat($shift->serviceProvider);
        $extCounts = $this->countByQualifications($extWorkers, $quals);

        $intSum = array_sum($intCounts);
        $extSum = array_sum($extCounts);
        $totalHeadcount = $intSum + $extSum;

        $intWorkMinutes = max(0, $durationMinutes - $breakMinutes);
        $extWorkMinutes = $durationMinutes;

        $intMinutes = [];
        $extMinutes = [];
        $intLabels  = [];
        $extLabels  = [];

        foreach ($quals as $q) {
            $qid = (int) $q->id;

            $intMinutes[$qid] = ($intCounts[$qid] ?? 0) * $intWorkMinutes;
            $extMinutes[$qid] = ($extCounts[$qid] ?? 0) * $extWorkMinutes;

            $intLabels[$qid] = $this->formatMinutesLabel($intMinutes[$qid]);
            $extLabels[$qid] = $this->formatMinutesLabel($extMinutes[$qid]);
        }

        $intMinutesSum = array_sum($intMinutes);
        $extMinutesSum = array_sum($extMinutes);
        $minutesTotal  = $intMinutesSum + $extMinutesSum;

        return [
            'craft' => (string) optional($shift->craft)->name,
            'date'  => $dateLabel,
            'room'  => $roomName,
            'start' => $startLabel,
            'end'   => $endLabel,

            'duration_label' => $this->formatMinutesLabel($durationMinutes),
            'break_label'    => $breakLabel,

            'int_counts'      => $intCounts,
            'ext_counts'      => $extCounts,
            'int_sum'         => $intSum,
            'ext_sum'         => $extSum,
            'total_headcount' => $totalHeadcount,

            'int_minutes'     => $intMinutes,
            'ext_minutes'     => $extMinutes,
            'int_labels'      => $intLabels,
            'ext_labels'      => $extLabels,

            'int_minutes_sum' => $intMinutesSum,
            'ext_minutes_sum' => $extMinutesSum,
            'minutes_total'   => $minutesTotal,

            'int_sum_label'   => $this->formatMinutesLabel($intMinutesSum),
            'ext_sum_label'   => $this->formatMinutesLabel($extMinutesSum),
            'total_label'     => $this->formatMinutesLabel($minutesTotal),
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int,mixed> $workers
     * @param Collection<int, ShiftQualification> $quals
     * @return array<int,int> id => count
     */
    private function countByQualifications(Collection $workers, Collection $quals): array
    {
        $out = [];
        foreach ($quals as $q) {
            $out[(int)$q->id] = 0;
        }

        foreach ($workers as $w) {
            $pivot = $w->pivot ?? null;

            $qid = (int)($pivot?->shift_qualification_id ?? 0);
            $qty = (int)($pivot?->shift_count ?? 1);
            $qty = max(1, $qty);

            if ($qid > 0 && array_key_exists($qid, $out)) {
                $out[$qid] += $qty;
            }
        }

        return $out;
    }

    /**
     * @return array{0:int} minutes
     */
    private function duration(mixed $start, mixed $end): array
    {
        $s = $this->parseTime($start);
        $e = $this->parseTime($end);

        if ($e->lessThanOrEqualTo($s)) {
            $e = $e->copy()->addDay();
        }

        $minutes = $s->diffInMinutes($e);

        return [$minutes];
    }

    private function parseTime(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }
        $str = substr((string)$value, 0, 5);
        return Carbon::createFromFormat('H:i', $str);
    }

    private function formatShiftTime(mixed $value): string
    {
        return $this->parseTime($value)->format('H:i') . ' Uhr';
    }

    private function formatShiftDate(mixed $value, string $lang): string
    {
        $d = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
        $label = $d->locale($lang)->translatedFormat('D, d.m.y');
        return str_replace('.', '', $label);
    }

    private function computePeriod(Collection $shifts): array
    {
        if ($shifts->isEmpty()) {
            return [null, null];
        }

        $start = $shifts->map(fn(Shift $s) => Carbon::parse($s->start_date))->sort()->first();
        $end   = $shifts->map(fn(Shift $s) => Carbon::parse($s->end_date))->sort()->last();

        return [$start, $end];
    }

    private function formatMinutesLabel(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return sprintf('%d Std. %02d min', $h, $m);
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $lastCol = $this->lastCol;

                $dataStart = 6;
                $totalRow = $dataStart + $this->rowCount;

                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FAFAFA'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle("A4:{$lastCol}{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D0D0D0'],
                        ],
                    ],
                ]);

                $n = (int) $this->qualColumns->count();

                $colG = 7;
                $shiftsStart = 8;
                $shiftsInternEnd   = $shiftsStart + $n - 1;
                $shiftsInternSum   = $shiftsInternEnd + 1;
                $shiftsExternStart = $shiftsInternSum + 1;
                $shiftsExternEnd   = $shiftsExternStart + $n - 1;
                $shiftsExternSum   = $shiftsExternEnd + 1;
                $headcountCol      = $shiftsExternSum + 1;

                $hoursStart        = $headcountCol + 1;
                $hoursInternEnd    = $hoursStart + $n - 1;
                $hoursInternSum    = $hoursInternEnd + 1;
                $hoursExternStart  = $hoursInternSum + 1;
                $hoursExternEnd    = $hoursExternStart + $n - 1;
                $hoursExternSum    = $hoursExternEnd + 1;
                $hoursTotalCol     = $hoursExternSum + 1;

                $blankCol          = $hoursTotalCol + 1;

                $separatorCols = [
                    $colG,
                    $shiftsInternSum,
                    $shiftsExternSum,
                    $headcountCol,
                    $hoursInternSum,
                    $hoursExternSum,
                    $hoursTotalCol,
                ];

                foreach ($separatorCols as $colIndex) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getStyle("{$colLetter}2:{$colLetter}{$totalRow}")->applyFromArray([
                        'borders' => [
                            'right' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => 'A6A6A6'],
                            ],
                        ],
                    ]);
                }

                $this->setWidth($sheet, 1, 36);
                $this->setWidth($sheet, 2, 16);
                $this->setWidth($sheet, 3, 22);
                $this->setWidth($sheet, 4, 12);
                $this->setWidth($sheet, 5, 12);
                $this->setWidth($sheet, 6, 16);
                $this->setWidth($sheet, 7, 14);

                for ($i = 0; $i < $n; $i++) {
                    $name = (string) ($this->qualColumns->get($i)?->name ?? '');
                    $len = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);

                    $wCount = $this->clamp($len + 2, 10, 24);
                    $wHours = max(16, $wCount);

                    $this->setWidth($sheet, $shiftsStart + $i, $wCount);
                    $this->setWidth($sheet, $shiftsExternStart + $i, $wCount);
                    $this->setWidth($sheet, $hoursStart + $i, $wHours);
                    $this->setWidth($sheet, $hoursExternStart + $i, $wHours);
                }

                $this->setWidth($sheet, $shiftsInternSum, 8);
                $this->setWidth($sheet, $shiftsExternSum, 8);
                $this->setWidth($sheet, $headcountCol, 12);

                $this->setWidth($sheet, $hoursInternSum, 18);
                $this->setWidth($sheet, $hoursExternSum, 18);
                $this->setWidth($sheet, $hoursTotalCol, 18);
                $this->setWidth($sheet, $blankCol, 3);

                $sheet->getStyle("A1:{$lastCol}4")->getAlignment()->setWrapText(true);

                $sheet->getStyle("B{$dataStart}:{$lastCol}{$totalRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("A{$dataStart}:A{$totalRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("C{$dataStart}:C{$totalRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $this->setNumberFormatRange(
                    $sheet,
                    $shiftsStart,
                    $shiftsInternEnd,
                    $dataStart,
                    $totalRow,
                    NumberFormat::FORMAT_NUMBER
                );
                $this->setNumberFormatRange(
                    $sheet,
                    $shiftsExternStart,
                    $shiftsExternEnd,
                    $dataStart,
                    $totalRow,
                    NumberFormat::FORMAT_NUMBER
                );
                $this->setNumberFormatRange(
                    $sheet,
                    $shiftsInternSum,
                    $shiftsInternSum,
                    $dataStart,
                    $totalRow,
                    NumberFormat::FORMAT_NUMBER
                );
                $this->setNumberFormatRange(
                    $sheet,
                    $shiftsExternSum,
                    $shiftsExternSum,
                    $dataStart,
                    $totalRow,
                    NumberFormat::FORMAT_NUMBER
                );
                $this->setNumberFormatRange(
                    $sheet,
                    $headcountCol,
                    $headcountCol,
                    $dataStart,
                    $totalRow,
                    NumberFormat::FORMAT_NUMBER
                );
            },
        ];
    }

    private function clamp(int $x, int $lo, int $hi): int
    {
        return max($lo, min($hi, $x));
    }

    private function setWidth(Worksheet $sheet, int $colIndex, float $width): void
    {
        $letter = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->getColumnDimension($letter)->setAutoSize(false);
        $sheet->getColumnDimension($letter)->setWidth($width);
    }

    private function setNumberFormatRange(
        Worksheet $sheet,
        int $colStart,
        int $colEnd,
        int $rowStart,
        int $rowEnd,
        string $format
    ): void {
        if ($colStart > $colEnd) {
            return;
        }

        $a = Coordinate::stringFromColumnIndex($colStart) . $rowStart;
        $b = Coordinate::stringFromColumnIndex($colEnd) . $rowEnd;

        $sheet->getStyle("$a:$b")->getNumberFormat()->setFormatCode($format);
    }
}
