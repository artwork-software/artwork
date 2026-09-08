<?php

namespace Artwork\Modules\Shift\Exports;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel-Export der gefilterten Ersatzfrei-Liste (Dashboard "Ersatzfreie Tage"):
 * Person, Gewerk, Verstoßdatum, Regel/Titel, Wert (Tage), Halbtag, Frist, Status, gewährt am/von, Begründung.
 */
class CompensationDaysExcelExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    /**
     * @param Collection<int, CompensationDayOff> $items mit user.assignedCrafts, violation.shiftRule, grantedByUser
     */
    public function __construct(private readonly Collection $items)
    {
    }

    public function title(): string
    {
        return __('Compensation days');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Person'),
            __('Craft'),
            __('Violation date'),
            __('Rule / title'),
            __('Value (days)'),
            __('Half day'),
            __('Deadline'),
            __('Status'),
            __('Granted on'),
            __('Granted by'),
            __('Reason'),
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->items->map(fn (CompensationDayOff $item): array => [
            $item->user ? trim($item->user->first_name . ' ' . $item->user->last_name) : '',
            $item->user?->relationLoaded('assignedCrafts')
                ? $item->user->assignedCrafts->pluck('name')->implode(', ')
                : '',
            self::formatDate($item->violation?->violation_date),
            $item->violation?->getDisplayName() ?? __('Manual'),
            (float) $item->value,
            match ($item->half_day_period) {
                'morning' => __('Morning'),
                'afternoon' => __('Afternoon'),
                default => '',
            },
            self::formatDate($item->deadline),
            self::statusLabel($item),
            self::formatDate($item->granted_date),
            $item->grantedByUser ? trim($item->grantedByUser->first_name . ' ' . $item->grantedByUser->last_name) : '',
            (string) ($item->reason ?? ''),
        ])->values()->all();
    }

    public static function statusLabel(CompensationDayOff $item): string
    {
        if ($item->isGranted()) {
            return __('Granted');
        }
        if ($item->deadline && Carbon::parse($item->deadline)->startOfDay()->lt(Carbon::today())) {
            return __('Overdue');
        }

        return __('Open');
    }

    private static function formatDate(mixed $date): string
    {
        if (!$date) {
            return '';
        }

        return Carbon::parse($date)->format('d.m.Y');
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
