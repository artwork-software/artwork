<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Exports\BiExportWorkbook;
use Artwork\Modules\BusinessIntelligence\Exports\BiProjectExport;
use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Projektunabhängiger Budget-Export (BI-Ausbau Baustein G): Grundgesamtheit sind
 * Budget-Zeilen aller Projekte mit Terminen im Zeitraum, angereichert um das
 * Sage-Ist. "KTO"/"KST" sind die ersten beiden Budget-Spalten (Position 0/1),
 * mandantenweit benannt über budget_column_settings; der Freitext-Filter matcht
 * per "beginnt mit" auf deren Zellwerte.
 */
class BiBudgetExportService
{
    public function __construct(
        private readonly BiDerivedValuesService $biDerivedValuesService
    ) {
    }

    /**
     * Filter-Optionen für das Export-Modal: die individuell vergebenen Namen
     * der beiden Kontierungs-Spalten + Kostenträger-Liste.
     *
     * @return array<string, mixed>
     */
    public function exportOptions(): array
    {
        return [
            'ktoColumnName' => BudgetColumnSetting::query()
                ->where('column_position', 0)->value('column_name') ?? 'KTO',
            'kstColumnName' => BudgetColumnSetting::query()
                ->where('column_position', 1)->value('column_name') ?? 'KST',
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name']),
            'sageApiEnabled' => $this->biDerivedValuesService->isSageApiEnabled(),
        ];
    }

    /**
     * Live-Trefferzahl je Kontierungs-Spalte für einen Freitext-Präfix —
     * Grundlage der "worin filtern?"-Entscheidung im Modal.
     *
     * @param array<int, int> $costCenterIds
     * @return array{kto: int, kst: int}
     */
    public function matchCounts(string $prefix, string $from, string $to, array $costCenterIds = []): array
    {
        $projectIds = $this->projectIdsInRange($from, $to, $costCenterIds);

        $countFor = function (int $position) use ($projectIds, $prefix): int {
            return SubPositionRow::query()
                ->whereHas(
                    'subPosition.mainPosition.table',
                    fn($query) => $query->whereIn('project_id', $projectIds)->where('is_template', false)
                )
                ->whereHas('cells', function ($query) use ($position, $prefix): void {
                    $query->where('value', 'like', str_replace(['%', '_'], ['\\%', '\\_'], $prefix) . '%')
                        ->whereHas('column', fn($column) => $column->where('position', $position));
                })
                ->count();
        };

        return ['kto' => $countFor(0), 'kst' => $countFor(1)];
    }

    public function cacheExportConfiguration(array $config): string
    {
        $token = Str::uuid()->toString();
        Cache::put('bi_budget_export_' . $token, $config, now()->addMinutes(30));
        Cache::put('bi_budget_export_status_' . $token, ['status' => 'pending'], now()->addMinutes(60));

        return $token;
    }

    public function generateAndStore(string $token): void
    {
        $config = Cache::get('bi_budget_export_' . $token);

        if (!$config) {
            Cache::put('bi_budget_export_status_' . $token, ['status' => 'failed'], now()->addMinutes(30));

            return;
        }

        try {
            $export = $this->buildExport($config);
            $export->store($this->storagePath($token), 'local');

            Cache::put('bi_budget_export_status_' . $token, ['status' => 'ready'], now()->addMinutes(60));
        } catch (Throwable $exception) {
            Cache::put(
                'bi_budget_export_status_' . $token,
                ['status' => 'failed', 'message' => $exception->getMessage()],
                now()->addMinutes(30)
            );

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(string $token): array
    {
        return Cache::get('bi_budget_export_status_' . $token) ?? ['status' => 'unknown'];
    }

    public function downloadStored(string $token): BinaryFileResponse
    {
        $path = $this->storagePath($token);

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Export not found or expired.');
        }

        $filename = 'budget-export-' . now()->format('Y-m-d_His') . '.xlsx';

        return response()
            ->download(Storage::disk('local')->path($path), $filename)
            ->deleteFileAfterSend();
    }

    private function storagePath(string $token): string
    {
        return 'bi-exports/budget-' . $token . '.xlsx';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function buildExport(array $config): BiExportWorkbook
    {
        $from = $config['date_from'];
        $to = $config['date_to'];
        $costCenterIds = $config['cost_center_ids'] ?? [];
        // Chips: [{column: 'kto'|'kst', prefix: '123'}] — gleiche Spalte ODER, Spalten UND
        $filters = $config['filters'] ?? [];
        $sageEnabled = $this->biDerivedValuesService->isSageApiEnabled();
        $restrictBookings = $sageEnabled && (bool) ($config['restrict_bookings_to_range'] ?? false);

        $projectIds = $this->projectIdsInRange($from, $to, $costCenterIds);
        $projectsById = Project::whereIn('id', $projectIds)->with('costCenter')->get()->keyBy('id');

        $rows = SubPositionRow::query()
            ->whereHas(
                'subPosition.mainPosition.table',
                fn($query) => $query->whereIn('project_id', $projectIds)->where('is_template', false)
            )
            ->with([
                'cells.column',
                'cells.sageAssignedData',
                'subPosition.mainPosition.table',
            ])
            ->get();

        $matched = $rows->filter(fn(SubPositionRow $row): bool => $this->rowMatchesFilters($row, $filters));

        // Wertspalten über den SPALTENNAMEN ausrichten: gleichnamige Spalten
        // unterschiedlicher Projekte teilen sich eine Excel-Spalte
        $valueColumnNames = $matched
            ->flatMap(fn(SubPositionRow $row) => $row->cells
                ->filter(fn($cell) => $cell->column?->type === 'empty')
                ->map(fn($cell) => $cell->column->name)
                ->filter(fn($name) => $name !== null && $name !== ''))
            ->unique()
            ->values();

        [$sheetRows, $bookingRows] = $this->buildRows(
            $matched,
            $projectsById,
            $valueColumnNames,
            $restrictBookings,
            $from,
            $to,
            $sageEnabled
        );

        $options = $this->exportOptions();

        $rowColumns = [
            'project_name', 'cost_center', 'main_position', 'sub_position',
            'kto', 'kst', 'position_text',
        ];
        $rowLabels = [
            'project_name' => __('Project name'),
            'cost_center' => __('Cost bearer'),
            'main_position' => __('Main position'),
            'sub_position' => __('Sub position'),
            'kto' => $options['ktoColumnName'],
            'kst' => $options['kstColumnName'],
            'position_text' => __('Position'),
        ];
        $rowFormats = [];
        foreach ($valueColumnNames as $index => $name) {
            $key = 'value_col_' . $index;
            $rowColumns[] = $key;
            $rowLabels[$key] = $name;
            $rowFormats[$key] = '#,##0.00 "€"';
        }
        if ($sageEnabled) {
            $rowColumns[] = 'sage_actual';
            $rowLabels['sage_actual'] = __('Sage actual');
            $rowFormats['sage_actual'] = '#,##0.00 "€"';
        }

        $bookingColumns = [
            'project_name', 'kto', 'kst', 'beleg_datum', 'buchungs_datum', 'beleg_nr',
            'buchungstext', 'betrag', 'sa_kto', 'kst_stelle', 'kst_traeger', 'kreditor', 'is_collective',
        ];
        $bookingLabels = [
            'project_name' => __('Project name'),
            'kto' => $options['ktoColumnName'],
            'kst' => $options['kstColumnName'],
            'beleg_datum' => __('Document date'),
            'buchungs_datum' => __('Booking date'),
            'beleg_nr' => __('Document number'),
            'buchungstext' => __('Booking text'),
            'betrag' => __('Amount'),
            'sa_kto' => __('General ledger account'),
            'kst_stelle' => __('Sage cost center'),
            'kst_traeger' => __('Sage cost bearer'),
            'kreditor' => __('Creditor'),
            'is_collective' => __('Collective booking'),
        ];

        $sheets = [new BiProjectExport($sheetRows, $rowColumns, $rowLabels, __('Budget rows'), $rowFormats)];

        if ($sageEnabled) {
            $sheets[] = new BiProjectExport(
                $bookingRows,
                $bookingColumns,
                $bookingLabels,
                __('Sage bookings'),
                ['betrag' => '#,##0.00 "€"']
            );
        }

        return new BiExportWorkbook($sheets);
    }

    /**
     * @param array<int, int> $costCenterIds
     * @return Collection<int, int>
     */
    private function projectIdsInRange(string $from, string $to, array $costCenterIds = []): Collection
    {
        $fromStart = Carbon::parse($from)->startOfDay();
        $toEnd = Carbon::parse($to)->endOfDay();

        return Project::query()
            ->where('is_group', false)
            ->when($costCenterIds !== [], fn($query) => $query->whereIn('cost_center_id', $costCenterIds))
            ->whereHas('events', function ($query) use ($fromStart, $toEnd): void {
                $query->where('start_time', '<=', $toEnd)
                    ->where(function ($inner) use ($fromStart): void {
                        $inner->where('end_time', '>=', $fromStart)
                            ->orWhere(function ($fallback) use ($fromStart): void {
                                $fallback->whereNull('end_time')->where('start_time', '>=', $fromStart);
                            });
                    });
            })
            ->pluck('id');
    }

    /**
     * @param array<int, array{column: string, prefix: string}> $filters
     */
    private function rowMatchesFilters(SubPositionRow $row, array $filters): bool
    {
        if ($filters === []) {
            return true;
        }

        $byColumn = [];
        foreach ($filters as $filter) {
            $byColumn[$filter['column']][] = (string) $filter['prefix'];
        }

        foreach ($byColumn as $column => $prefixes) {
            $position = $column === 'kst' ? 1 : 0;
            $value = (string) ($this->cellValueAtPosition($row, $position) ?? '');

            $matchesAny = false;
            foreach ($prefixes as $prefix) {
                if ($prefix !== '' && str_starts_with($value, $prefix)) {
                    $matchesAny = true;
                    break;
                }
            }

            if (!$matchesAny) {
                return false;
            }
        }

        return true;
    }

    private function cellValueAtPosition(SubPositionRow $row, int $position): ?string
    {
        return $row->cells->first(fn($cell) => $cell->column?->position === $position)?->value;
    }

    /**
     * @param Collection<int, SubPositionRow> $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function buildRows(
        Collection $rows,
        Collection $projectsById,
        Collection $valueColumnNames,
        bool $restrictBookings,
        string $from,
        string $to,
        bool $sageEnabled = true
    ): array {
        $fromStart = Carbon::parse($from)->startOfDay();
        $toEnd = Carbon::parse($to)->endOfDay();
        $valueColumnIndex = $valueColumnNames->flip();

        $sheetRows = [];
        $bookingRows = [];

        foreach ($rows as $row) {
            $table = $row->subPosition?->mainPosition?->table;
            $project = $table ? $projectsById->get($table->project_id) : null;

            $kto = $this->cellValueAtPosition($row, 0) ?? '';
            $kst = $this->cellValueAtPosition($row, 1) ?? '';

            $sheetRow = [
                'project_name' => $project?->name ?? '',
                'cost_center' => $project?->costCenter?->name ?? '',
                'main_position' => $row->subPosition?->mainPosition?->name ?? '',
                'sub_position' => $row->subPosition?->name ?? '',
                'kto' => $kto,
                'kst' => $kst,
                'position_text' => $this->cellValueAtPosition($row, 2) ?? '',
            ];

            foreach ($valueColumnNames as $index => $name) {
                $sheetRow['value_col_' . $index] = '';
            }

            $sageSum = 0.0;
            $hasBookings = false;

            foreach ($row->cells as $cell) {
                $column = $cell->column;

                if ($column?->type === 'empty' && $valueColumnIndex->has($column->name)) {
                    $sheetRow['value_col_' . $valueColumnIndex->get($column->name)] = $cell->value ?? '';
                }

                if (!$sageEnabled) {
                    continue;
                }

                foreach ($cell->sageAssignedData as $booking) {
                    // Belegdatum ist varchar — unparsebare Werte fallen beim
                    // Datumsfilter bewusst heraus statt den Export zu brechen
                    if ($restrictBookings) {
                        try {
                            $belegDatum = Carbon::parse((string) $booking->belegdatum);
                        } catch (Throwable) {
                            continue;
                        }

                        if ($belegDatum->lt($fromStart) || $belegDatum->gt($toEnd)) {
                            continue;
                        }
                    }

                    $hasBookings = true;
                    $sageSum += (float) $booking->buchungsbetrag;

                    $bookingRows[] = [
                        'project_name' => $project?->name ?? '',
                        'kto' => $kto,
                        'kst' => $kst,
                        'beleg_datum' => (string) $booking->belegdatum,
                        'buchungs_datum' => (string) $booking->buchungsdatum,
                        'beleg_nr' => (string) $booking->belegnummer,
                        'buchungstext' => (string) $booking->buchungstext,
                        'betrag' => (float) $booking->buchungsbetrag,
                        'sa_kto' => (string) $booking->sa_kto,
                        'kst_stelle' => (string) $booking->kst_stelle,
                        'kst_traeger' => (string) $booking->kst_traeger,
                        'kreditor' => (string) $booking->kreditor,
                        'is_collective' => $booking->is_collective_booking ? __('Yes') : __('No'),
                    ];
                }
            }

            if ($sageEnabled) {
                $sheetRow['sage_actual'] = $hasBookings ? round($sageSum, 2) : '';
            }
            $sheetRows[] = $sheetRow;
        }

        return [$sheetRows, $bookingRows];
    }
}
