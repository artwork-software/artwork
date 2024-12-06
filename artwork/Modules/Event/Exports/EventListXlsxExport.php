<?php

namespace Artwork\Modules\Event\Exports;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventListXlsxExport implements FromView, WithStyles
{
    use Exportable;

    private array $rows;

    private array $columns;

    public function __construct(private readonly ViewFactory $viewFactory)
    {
    }

    public function view(): View
    {
        return $this->viewFactory->make(
            'exports.eventList',
            [
                'columns' => $this->columns,
                'rows' => $this->rows
            ]
        );
    }

    public function setColumns(array $columns): EventListXlsxExport
    {
        $this->columns = $columns;

        return $this;
    }

    public function setRows(array $rows): self
    {
        $this->rows = $rows;

        return $this;
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
