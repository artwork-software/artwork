<?php

namespace Artwork\Modules\Contract\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractExcelExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected Collection $contracts;
    public string $language;

    public function __construct(
        Collection $contracts,
        string $language,
    ) {
        $this->contracts = $contracts;
        $this->language = $language;
    }

    public function view(): View
    {
        return view('exports.contracts', [
            'contracts' => $this->contracts,
            'user' => auth()->user(),
            'language' => $this->language,
        ]);
    }

    /**
     * @param Worksheet $sheet
     * @return array<int, array<string, array<string, mixed>>>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
    public function styles(Worksheet $sheet): array
    {
        return [
            //first row bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}
