<?php

namespace Artwork\Modules\ExternalIssue\Exports;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Externes Materialausgabe-Buch als Excel-Tabelle.
 */
class ExternalIssueListExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    /**
     * @param Collection<int, ExternalIssue> $issues mit geladenen issuedBy, receivedBy, project,
     *                                               articles, specialItems, files
     */
    public function __construct(private readonly Collection $issues)
    {
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Name'),
            __('External name'),
            __('Material value'),
            __('Issue date'),
            __('Return date'),
            __('Return status'),
            __('Issued by'),
            __('Received by'),
            __('Project'),
            __('Articles'),
            __('Special items'),
            __('Files'),
            __('Return remarks'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return $this->issues
            ->map(fn (ExternalIssue $issue): array => [
                $issue->name,
                $issue->external_name,
                $issue->material_value !== null ? (float) $issue->material_value : null,
                $issue->issue_date?->format('d.m.Y'),
                $issue->return_date?->format('d.m.Y'),
                $this->returnStatus($issue),
                $this->fullName($issue->issuedBy),
                $this->fullName($issue->receivedBy),
                $issue->project?->name,
                $issue->articles->count(),
                $issue->specialItems->count(),
                $issue->files->count(),
                $issue->return_remarks,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    /**
     * Dieselbe Rangfolge wie das Status-Abzeichen der Liste.
     */
    private function returnStatus(ExternalIssue $issue): string
    {
        if ($issue->counts_as_returned) {
            return __('Returned');
        }
        if ($issue->overdue_unreturned) {
            return __('Return open – material blocked');
        }
        if ($issue->return_status === ExternalIssue::RETURN_STATUS_NOT_RETURNED) {
            return __('Not returned');
        }

        return __('Issued');
    }

    private function fullName(mixed $user): ?string
    {
        return $user ? trim($user->first_name . ' ' . $user->last_name) : null;
    }
}
