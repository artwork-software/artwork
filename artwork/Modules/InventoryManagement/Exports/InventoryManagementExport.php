<?php

namespace Artwork\Modules\InventoryManagement\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

readonly class InventoryManagementExport implements FromView, WithStyles
{
    use Exportable;

    public function __construct(
        private Collection $columns,
        private Collection $crafts
    ) {
    }

    public function view(): View
    {
        return view(
            'exports.inventoryManagement',
            [
                'columns' => $this->columns,
                'crafts' => $this->crafts
            ]
        );
    }

    /**
     * @return array<int, array<string, array<string, mixed>>>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
    public function styles(Worksheet $sheet): array
    {
        return [
            //first row bold
            1 => ['font' => ['bold' => true]]
        ];
    }
}
