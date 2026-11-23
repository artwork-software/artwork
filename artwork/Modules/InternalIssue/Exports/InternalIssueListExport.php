<?php

namespace Artwork\Modules\InternalIssue\Exports;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Materialausgabe-Buch (intern) als Excel-Tabelle.
 */
class InternalIssueListExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    /**
     * @param Collection<int, InternalIssue> $issues mit geladenen room, project, responsibleUsers,
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
            __('Start'),
            __('End'),
            __('Room'),
            __('Project'),
            __('Responsible'),
            __('Articles'),
            __('Special items'),
            __('Files'),
            __('Status'),
            __('Notes'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|null>>
     */
    public function array(): array
    {
        return $this->issues
            ->map(fn (InternalIssue $issue): array => [
                $issue->name,
                $this->formatDateTime($issue->start_date, $issue->start_time),
                $this->formatDateTime($issue->end_date, $issue->end_time),
                $issue->room?->name,
                $issue->project?->name,
                $issue->responsibleUsers
                    ->map(static fn ($user): string => trim($user->first_name . ' ' . $user->last_name))
                    ->implode(', '),
                $issue->articles->count(),
                $issue->specialItems->count(),
                $issue->files->count(),
                !$issue->special_items_done && $issue->specialItems->isNotEmpty()
                    ? __('Special items open')
                    : __('Completed'),
                $issue->notes,
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

    private function formatDateTime(mixed $date, ?string $time): ?string
    {
        if (!$date) {
            return null;
        }

        return trim(Carbon::parse($date)->format('d.m.Y') . ' ' . substr((string) $time, 0, 5));
    }
}
