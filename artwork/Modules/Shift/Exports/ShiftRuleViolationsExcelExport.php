<?php

namespace Artwork\Modules\Shift\Exports;

use Artwork\Modules\Shift\Exports\Support\ViolationMeasureFormatter;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\ShiftRuleViolationRepository;
use Carbon\Carbon;
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
 * Excel-Export der Regelverstöße (Filter wie die Liste "Offene Verstöße", Zeitraum Pflicht).
 * FromQuery: Laravel Excel liest die Query in Chunks (chunkSize) — auch tausende Verstöße
 * landen nie komplett im Speicher.
 */
class ShiftRuleViolationsExcelExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithCustomChunkSize
{
    use Exportable;

    private readonly ViolationMeasureFormatter $formatter;

    /**
     * @param array<string, mixed> $filters siehe ShiftRuleViolationRepository::filteredQuery()
     */
    public function __construct(
        private readonly ShiftRuleViolationRepository $repository,
        private readonly array $filters,
        private readonly string $sortDirection = 'desc',
        private readonly ?string $locale = null,
    ) {
        $this->formatter = new ViolationMeasureFormatter($locale);
    }

    public function query(): Builder
    {
        return $this->repository->filteredQuery($this->filters)
            ->with(ShiftRuleViolationRepository::listRelations())
            ->orderBy('violation_date', $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return $this->tr('Violations');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            $this->tr('Date'),
            $this->tr('Person'),
            $this->tr('Crafts'),
            $this->tr('Rule'),
            $this->tr('Type'),
            $this->tr('Measured value'),
            $this->tr('Severity'),
            $this->tr('Status'),
            $this->tr('Compensation days'),
            $this->tr('Compensation deadline'),
            $this->tr('Granted on'),
            $this->tr('Shift'),
            $this->tr('Processed by'),
            $this->tr('Processed on'),
            $this->tr('Comment'),
        ];
    }

    /**
     * @param ShiftRuleViolation $violation
     * @return array<int, mixed>
     */
    public function map($violation): array
    {
        $grantedOn = $violation->compensationDayOffs
            ->filter(fn ($day) => $day->granted_at !== null)
            ->map(fn ($day) => $day->granted_date ? Carbon::parse($day->granted_date)->format('d.m.Y') : null)
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            self::date($violation->violation_date),
            $violation->user ? trim($violation->user->first_name . ' ' . $violation->user->last_name) : '',
            $violation->user?->relationLoaded('assignedCrafts')
                ? $violation->user->assignedCrafts->pluck('name')->implode(', ')
                : '',
            $violation->getDisplayName(),
            $violation->shiftRule
                ? $this->formatter->ruleTypeLabel($violation->shiftRule->trigger_type)
                : $this->tr('Manual'),
            $this->formatter->format($violation),
            $violation->severity === 'error' ? $this->tr('Error') : $this->tr('Warning'),
            $this->statusLabel($violation->status),
            $violation->compensation_days !== null ? (float) $violation->compensation_days : '',
            self::date($violation->compensation_deadline),
            $grantedOn,
            $this->shiftLabel($violation),
            $violation->resolvedByUser
                ? trim($violation->resolvedByUser->first_name . ' ' . $violation->resolvedByUser->last_name)
                : '',
            $violation->resolved_at?->format('d.m.Y H:i') ?? '',
            (string) ($violation->ignore_reason ?: ($violation->compensation_reason ?: ($violation->reason ?? ''))),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            'active' => $this->tr('Active'),
            'resolved' => $this->tr('Processed'),
            'ignored' => $this->tr('Ignored'),
            default => (string) $status,
        };
    }

    private function shiftLabel(ShiftRuleViolation $violation): string
    {
        $shift = $violation->shift;
        if (!$shift) {
            return '';
        }

        $parts = [self::date($shift->start_date)];
        if ($shift->start || $shift->end) {
            $parts[] = trim(($shift->start ?? '') . ' – ' . ($shift->end ?? ''), ' –');
        }
        if ($shift->room?->name) {
            $parts[] = $shift->room->name;
        }

        return implode(' ', array_filter($parts));
    }

    private static function date(mixed $date): string
    {
        return $date ? Carbon::parse($date)->format('d.m.Y') : '';
    }

    private function tr(string $key): string
    {
        return __($key, [], $this->locale);
    }
}
