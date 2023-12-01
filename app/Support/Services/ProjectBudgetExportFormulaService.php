<?php

namespace App\Support\Services;

use Illuminate\Support\Collection;

class ProjectBudgetExportFormulaService
{
    /**
     * @var string[]
     */
    private static $cellCharacters = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
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
     * @param int $linkedColumnId
     * @param int $currentRowCount
     * @return string
     */
    private function determineExcelColumn(Collection $columns, int $linkedColumnId, int $currentRowCount): string
    {
        return
            //add +1 to desired column index because first cell is reserved for headings
            self::$cellCharacters[($columns->search(fn($column) => $column->id === $linkedColumnId) + 1)] .
            //add +1 to rowCount because it reflects the current row, not the next row which should be added right now
            ($currentRowCount + 1);
    }
}
