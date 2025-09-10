<?php

namespace App\Exports;

use Artwork\Modules\ArtistResidency\Models\Artist;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArtistExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(private readonly Collection $artists)
    {
    }

    public function view(): View
    {
        return view('exports.artists', [
            'data' => $this->artists,
            'language' => app()->getLocale(),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            //first row bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}
