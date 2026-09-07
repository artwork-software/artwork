<?php

namespace Artwork\Modules\Shift\Exports;

use Artwork\Modules\Shift\Exports\Support\CommittedShiftChangePresenter;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel-Export der Änderungsübersicht (Änderungen nach Festschreibung): Zeitpunkt, Schicht (Datum, Zeit,
 * Raum, Gewerk), betroffene Person, Art der Änderung, Alt → Neu, Geändert von, Status, bestätigt von/am.
 * Die Query kommt aus ShiftPlanRequestController::committedShiftChangesBaseQuery() (gleiche Filter wie
 * die Liste), FromQuery liest sie in Chunks.
 */
class CommittedShiftChangesExcelExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithCustomChunkSize
{
    use Exportable;

    public function __construct(
        private readonly Builder $query,
        private readonly ?string $locale = null,
    ) {
    }

    public function query(): Builder
    {
        return $this->query
            ->with([
                'shift' => fn ($q) => $q
                    ->select('id', 'craft_id', 'room_id', 'start_date', 'end_date', 'start', 'end')
                    ->without(['users', 'freelancer', 'serviceProvider', 'committedBy'])
                    ->with(['craft:id,name,abbreviation', 'room:id,name']),
                'craft:id,name,abbreviation',
                'changedBy:id,first_name,last_name',
                'acknowledgedBy:id,first_name,last_name',
            ])
            ->orderByDesc('changed_at')
            ->orderByDesc('id');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return $this->tr('Change list');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            $this->tr('Timestamp'),
            $this->tr('Shift date'),
            $this->tr('Time'),
            $this->tr('Room'),
            $this->tr('Craft'),
            $this->tr('Affected person'),
            $this->tr('Kind of change'),
            $this->tr('Before'),
            $this->tr('After'),
            $this->tr('Changed by'),
            $this->tr('Status'),
            $this->tr('Confirmed by'),
            $this->tr('Confirmed on'),
        ];
    }

    /**
     * @param CommittedShiftChange $change
     * @return array<int, mixed>
     */
    public function map($change): array
    {
        $presented = CommittedShiftChangePresenter::present($change);
        $shift = $change->shift;
        $assignment = $presented['field_changes']['assignment'] ?? null;
        $affected = is_array($assignment) ? ($assignment['user_name'] ?? null) : null;

        return [
            $presented['changed_at_formatted'] ?? '',
            $shift?->start_date?->format('d.m.Y') ?? '',
            $shift && ($shift->start || $shift->end)
                ? trim(implode(' – ', array_filter([$shift->start, $shift->end])))
                : '',
            $shift?->room?->name ?? '',
            $shift?->craft?->abbreviation ?? $shift?->craft?->name ?? $change->craft?->abbreviation ?? '',
            (string) ($affected ?? ''),
            CommittedShiftChangePresenter::describe($presented, $this->locale),
            $this->label($presented['before_label'] ?? null),
            $this->label($presented['after_label'] ?? null),
            (string) ($presented['changed_by_name'] ?? ''),
            $presented['acknowledged'] ? $this->tr('Approval granted') : $this->tr('Open'),
            $change->acknowledgedBy
                ? trim($change->acknowledgedBy->first_name . ' ' . $change->acknowledgedBy->last_name)
                : '',
            $change->acknowledged_at?->format('d.m.Y H:i') ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /** "free" wird wie in der Liste übersetzt, alle anderen Labels (Datum/Zeit) bleiben roh. */
    private function label(?string $label): string
    {
        if ($label === null || $label === '') {
            return '';
        }

        return $label === 'free' ? $this->tr('Free') : $label;
    }

    private function tr(string $key): string
    {
        return __($key, [], $this->locale);
    }
}
