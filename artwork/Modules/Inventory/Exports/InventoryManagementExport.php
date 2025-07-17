<?php

namespace Artwork\Modules\Inventory\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Contracts\View\Factory as ViewFactory;

class InventoryManagementExport implements FromView, WithStyles
{
    use Exportable;

    private Collection $columns;

    private Collection $crafts;

    public function __construct(private readonly ViewFactory $viewFactory)
    {
    }

    public function view(): View
    {
        return $this->viewFactory->make(
            'exports.inventoryManagement',
            [
                'columns' => $this->columns,
                'crafts' => $this->crafts
            ]
        );
    }

    public function setColumns(Collection $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function setCrafts(Collection $crafts): self
    {
        $this->crafts = $crafts;

        return $this;
    }

    public function getCrafts(): Collection
    {
        return $this->crafts;
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
