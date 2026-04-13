<?php

namespace Artwork\Modules\Crm\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CrmContactsExcelExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly Collection $contacts,
        private readonly array $columns,
        private readonly Collection $properties,
    ) {
    }

    public function view(): View
    {
        return view('exports.crm-contacts', [
            'contacts' => $this->contacts,
            'columns' => $this->columns,
            'properties' => $this->properties,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
