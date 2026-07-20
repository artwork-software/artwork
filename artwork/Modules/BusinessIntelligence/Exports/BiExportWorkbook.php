<?php

namespace Artwork\Modules\BusinessIntelligence\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BiExportWorkbook implements WithMultipleSheets
{
    use Exportable;

    /**
     * @param array<int, BiProjectExport> $sheets
     */
    public function __construct(private readonly array $sheets)
    {
    }

    /**
     * @return array<int, BiProjectExport>
     */
    public function sheets(): array
    {
        return $this->sheets;
    }
}
