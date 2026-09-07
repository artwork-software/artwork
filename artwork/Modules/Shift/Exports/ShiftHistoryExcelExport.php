<?php

namespace Artwork\Modules\Shift\Exports;

use Artwork\Modules\Shift\Exports\Support\ShiftActivityPresenter;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Activitylog\Models\Activity;

/**
 * Excel-Export des Schichtverlaufs: Zeitpunkt, Von, Aktion, Schicht (Datum, Zeit, Raum, Gewerk), Details.
 * Query kommt aus ShiftHistoryQueryService (identisch zum Modal), Texte aus ShiftActivityPresenter.
 */
class ShiftHistoryExcelExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithCustomChunkSize
{
    use Exportable;

    private readonly ShiftActivityPresenter $presenter;

    /** @var array<int, Shift>|null Live-Schichten (auch soft-deleted) für die Schicht-Spalten */
    private ?array $shiftsById = null;

    /**
     * @param array<int, int> $matchedShiftIds
     */
    public function __construct(
        private readonly Builder $activityQuery,
        private readonly array $matchedShiftIds,
        private readonly ?string $locale = null,
    ) {
        $this->presenter = new ShiftActivityPresenter($locale);
    }

    public function query(): Builder
    {
        return $this->activityQuery;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return $this->tr('Shift history');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            $this->tr('Timestamp'),
            $this->tr('Changed by'),
            $this->tr('Action'),
            $this->tr('Shift date'),
            $this->tr('Time'),
            $this->tr('Room'),
            $this->tr('Craft'),
            $this->tr('Details'),
        ];
    }

    /**
     * @param Activity $log
     * @return array<int, mixed>
     */
    public function map($log): array
    {
        $shift = $this->presenter->shiftDetails($log, $this->shiftsById());
        $details = array_merge([$this->presenter->message($log)], $this->presenter->changeLines($log));

        return [
            $log->created_at?->format('d.m.Y H:i') ?? '',
            $this->presenter->causerName($log),
            $this->presenter->categoryLabel($log),
            $shift['date'],
            $shift['time'],
            $shift['room'],
            $shift['craft'],
            implode("\n", array_filter($details, fn ($line) => $line !== '')),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('H')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return array<int, Shift>
     */
    private function shiftsById(): array
    {
        if ($this->shiftsById === null) {
            $this->shiftsById = $this->matchedShiftIds === []
                ? []
                : Shift::withTrashed()
                    ->whereIn('id', $this->matchedShiftIds)
                    ->select(['id', 'craft_id', 'room_id', 'start_date', 'end_date', 'start', 'end', 'deleted_at'])
                    ->with(['room:id,name', 'craft:id,name,abbreviation'])
                    ->get()
                    ->keyBy('id')
                    ->all();
        }

        return $this->shiftsById;
    }

    private function tr(string $key): string
    {
        return __($key, [], $this->locale);
    }
}
