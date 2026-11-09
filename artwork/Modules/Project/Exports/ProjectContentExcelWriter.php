<?php

namespace Artwork\Modules\Project\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Projektinhalte als Excel: ein Projekt pro Zeile, die gewählten Spalten nebeneinander, mehrzeilige Zellen.
 * Alle Werte werden als Text geschrieben, damit Inhalte wie "=…" nie als Formel ausgeführt werden.
 */
class ProjectContentExcelWriter
{
    /** Excel nimmt höchstens 32.767 Zeichen je Zelle an. */
    private const MAX_CELL_LENGTH = 32767;

    /**
     * @param array<int, string> $titles
     * @param array<int, array<int, string>> $rows
     * @return string Pfad der erzeugten Datei
     */
    public function write(array $titles, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr(__('Project contents'), 0, 31));

        $columnCount = max(count($titles), 1);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        foreach ($titles as $index => $title) {
            $sheet->setCellValueExplicit([$index + 1, 1], $this->cellText($title), DataType::TYPE_STRING);
        }

        foreach ($rows as $rowIndex => $values) {
            foreach ($values as $columnIndex => $value) {
                $sheet->setCellValueExplicit(
                    [$columnIndex + 1, $rowIndex + 2],
                    $this->cellText($value),
                    DataType::TYPE_STRING
                );
            }
        }

        $lastRow = count($rows) + 1;
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E7E7']],
        ]);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $longestLine = max(array_map(
                fn (array $values): int => $this->longestLine($values[$columnIndex - 1] ?? ''),
                [[$titles[$columnIndex - 1] ?? ''], ...$rows]
            ));
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))
                ->setWidth(min(max($longestLine + 2, 12), 60));
        }

        $path = tempnam(sys_get_temp_dir(), 'project-content-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function cellText(string $value): string
    {
        return mb_substr($value, 0, self::MAX_CELL_LENGTH);
    }

    private function longestLine(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        return max(array_map('mb_strlen', explode("\n", $value)));
    }
}
