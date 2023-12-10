<?php

namespace App\Support\Services;

use Illuminate\Support\Collection;

class ProjectBudgetExportFormulaService
{
    /**
     * @var string[]
     */
    private static array $cellCharacters = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
        'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
    ];

    /**
     * @param Collection $columns
     * @param int $currentRowCount
     * @param int $firstLinkedColumnId
     * @param string $operator
     * @param int $secondLinkedColumnId
     * @return string
     */
    public function createFormula(
        Collection $columns,
        int        $currentRowCount,
        int        $firstLinkedColumnId,
        string     $operator,
        int        $secondLinkedColumnId
    ): string
    {
        return sprintf(
            '=%s%s%s',
            $this->determineExcelColumn($columns, $firstLinkedColumnId, $currentRowCount),
            $operator,
            $this->determineExcelColumn($columns, $secondLinkedColumnId, $currentRowCount)
        );
    }

    /**
     * @param Collection $columns
     * @param int $columnId
     * @param int $currentRowCount
     * @return string
     */
    public function determineExcelColumn(Collection $columns, int $columnId, int $currentRowCount): string
    {
        return
            //add +1 to desired column index because first cell is reserved for headings
            self::$cellCharacters[($columns->search(fn($column) => $column->id === $columnId) + 1)] .
            //add +1 to rowCount because it reflects the current row, not the next row which should be added right now
            ($currentRowCount + 1);
    }

    /**
     * @param array $columnsToSum
     * @return string
     */
    public function createColumnSumRangeFormula(array $columnsToSum): string
    {
        if (count($columnsToSum) === 1) {
            //if there is no range append the same cell to formula twice
            return sprintf(
                '=SUM(%s:%s)',
                $columnsToSum[0],
                $columnsToSum[0]
            );
        } else {
            return sprintf(
                '=SUM(%s:%s)',
                array_shift($columnsToSum),
                array_pop($columnsToSum)
            );
        }
    }

    /**
     * @param array $columnsToSum
     * @return string
     */
    public function createColumnSumSeparatedFormula(array $columnsToSum): string
    {
        return sprintf(
            "=SUM(%s)",
            implode(
                ',',
                $columnsToSum
            )
        );
    }

    /**
     * @param string $firstColumnCell
     * @param string $secondColumnCell
     * @return string
     */
    public function createSubtractionFormula(string $firstColumnCell, string $secondColumnCell): string
    {
        return sprintf("=%s-%s", $firstColumnCell, $secondColumnCell);
    }
}
