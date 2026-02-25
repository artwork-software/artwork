<?php

namespace Artwork\Modules\ArtistResidency\Exports;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArtistResidencyExcelExport implements FromView, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    use Exportable;

    protected Collection $artistResidencies;
    protected object $project;
    public string $language;

    public function __construct(
        Collection $artistResidencies,
        object $project,
        string $language,
    ) {
        $this->artistResidencies = $artistResidencies;
        $this->project = $project;
        $this->language = $language;
    }

    public function view(): View
    {
        return view('exports.artist-residency-per-diem', [
            'artistResidencies' => $this->artistResidencies,
            'project' => $this->project,
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

    /**
     * @return string[]
     */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DATETIME,
            'D' => NumberFormat::FORMAT_DATE_DATETIME,
            'F' => NumberFormat::FORMAT_CURRENCY_EUR,
            'H' => NumberFormat::FORMAT_CURRENCY_EUR,
            'J' => NumberFormat::FORMAT_CURRENCY_EUR,
            'K' => NumberFormat::FORMAT_CURRENCY_EUR,
            'L' => NumberFormat::FORMAT_CURRENCY_EUR,
            'M' => NumberFormat::FORMAT_CURRENCY_EUR,
        ];
    }
}
