<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Support\ExportPeriodLimit;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Gemeinsame Query-Logik des Schichtverlaufs (Modal = ShiftHistoryController::index, Excel-Export =
 * ShiftHistoryController::export): Zeitraum, Gewerk, einzelne Schicht, Freitextsuche, Sortierung.
 * Bewusst EINE Quelle, damit Modal und Export dieselben Einträge liefern.
 */
class ShiftHistoryQueryService
{
    /**
     * Filter aus Query-Parametern (craftId, shiftId, start_date, end_date, search, sort).
     * Zeitraum über ExportPeriodLimit::resolveBounds(): ohne Angabe aktueller Monat, eine Grenze →
     * höchstens ein Jahr ab/bis dahin; länger als ein Jahr → ValidationException (422).
     *
     * @param array<string, mixed> $params
     * @return array{
     *     craft_id: int, shift_id: int, start_date: Carbon, end_date: Carbon,
     *     start_ymd: string, end_ymd: string, search: string, sort_by_shift_day: bool
     * }
     */
    public function resolveFilters(array $params): array
    {
        // Zeitraum-Deckel (Modal + Excel-Export): fehlende Grenzen auffüllen, höchstens ein Jahr, sonst 422
        [$startDate, $endDate] = ExportPeriodLimit::resolveBounds(
            !empty($params['start_date']) ? (string) $params['start_date'] : null,
            !empty($params['end_date']) ? (string) $params['end_date'] : null,
            'end_date',
            config('app.timezone', 'Europe/Berlin')
        );

        return [
            'craft_id' => max(0, (int) ($params['craftId'] ?? 0)),
            'shift_id' => max(0, (int) ($params['shiftId'] ?? 0)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_ymd' => $startDate->toDateString(),
            'end_ymd' => $endDate->toDateString(),
            'search' => trim((string) ($params['search'] ?? '')),
            'sort_by_shift_day' => ($params['sort'] ?? null) === 'shift_day',
        ];
    }

    /**
     * Schichten im Zeitraum (Overlaps!) – bewusst inkl. soft-deleted (withTrashed): Eine gelöschte
     * Schicht soll mit ihrem KOMPLETTEN Verlauf sichtbar bleiben. Solange die Row existiert (auch
     * soft-deleted), liefert sie das Schicht-Start-Datum für den Zeitraum-Filter.
     *
     * @param array{craft_id: int, start_ymd: string, end_ymd: string} $filters
     */
    public function shiftQuery(array $filters): Builder
    {
        return Shift::withTrashed()
            ->when($filters['craft_id'] > 0, fn ($q) => $q->where('craft_id', $filters['craft_id']))
            ->startAndEndDateOverlap($filters['start_ymd'], $filters['end_ymd']);
    }

    /**
     * IDs der Schichten mit Start im Zeitraum, vereinigt mit force-gelöschten Schichten, die nur noch
     * über den im Log gespeicherten Snapshot (properties->shift_snapshot) erreichbar sind.
     *
     * Performance: Die Snapshot-Abfrage wertet einen JSON-Pfad ohne Index aus und wird deshalb hart
     * eingegrenzt (event='deleted', whereNotIn bekannter IDs).
     *
     * @param array{craft_id: int, shift_id: int, start_ymd: string, end_ymd: string} $filters
     * @param array<int, int> $shiftIds
     * @return array<int, int>
     */
    public function matchedShiftIds(array $filters, array $shiftIds): array
    {
        $snapshotShiftIds = Activity::query()
            ->where('log_name', 'shift')
            ->where('subject_type', Shift::class)
            ->where('event', 'deleted')
            ->when($filters['shift_id'] > 0, fn ($query) => $query->where('subject_id', $filters['shift_id']))
            ->when(!empty($shiftIds), fn ($q) => $q->whereNotIn('subject_id', $shiftIds))
            ->whereBetween('properties->shift_snapshot->start_date', [$filters['start_ymd'], $filters['end_ymd']])
            ->when($filters['craft_id'] > 0, fn ($q) => $q->where('properties->craft_id', $filters['craft_id']))
            ->distinct()
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge(array_map('intval', $shiftIds), $snapshotShiftIds)));
    }

    /**
     * Activity-Query (Spatie activity_log) über die Schicht-Zugehörigkeit, NICHT über den Fortbestand
     * der Schicht; plus Sammel-Einträge (Festschreibung KW/Zeitraum) ohne Subject über
     * properties->commit_summary. Sortiert, ohne Paginierung — Aufrufer paginieren oder cursorn.
     *
     * @param array{
     *     craft_id: int, shift_id: int, start_ymd: string, end_ymd: string,
     *     search: string, sort_by_shift_day: bool
     * } $filters
     * @param array<int, int> $matchedShiftIds
     */
    public function activityQuery(array $filters, array $matchedShiftIds): Builder
    {
        $craftId = $filters['craft_id'];
        $shiftId = $filters['shift_id'];
        $startYmd = $filters['start_ymd'];
        $endYmd = $filters['end_ymd'];
        $search = $filters['search'];
        $includeCommitSummaries = $shiftId === 0 || in_array($shiftId, $matchedShiftIds, true);

        return Activity::query()
            ->where('log_name', 'shift')
            // Zu-/Absagen der Mitarbeitenden werden immer geloggt, aber nur angezeigt, wenn das
            // Setting es erlaubt. NULL-safe filtern: "event NOT IN (...)" würde Einträge ohne
            // event-Wert mit verwerfen.
            ->when(
                !app(\App\Settings\ShiftSettings::class)->shift_confirmation_in_history,
                function ($query): void {
                    $query->where(function ($inner): void {
                        $inner->whereNull('event')
                            ->orWhereNotIn('event', ['confirmation_accepted', 'confirmation_declined']);
                    });
                }
            )
            ->where(function ($query) use (
                $matchedShiftIds,
                $startYmd,
                $endYmd,
                $craftId,
                $shiftId,
                $includeCommitSummaries
            ): void {
                $query->where(function ($subjectQuery) use ($matchedShiftIds): void {
                    $subjectQuery->where('subject_type', Shift::class)
                        ->whereIn('subject_id', $matchedShiftIds);
                })->when($includeCommitSummaries, function ($query) use ($startYmd, $endYmd, $craftId, $shiftId): void {
                    $query->orWhere(function ($summaryQuery) use ($startYmd, $endYmd, $craftId, $shiftId): void {
                        $summaryQuery->whereNull('subject_id')
                            ->where('properties->commit_summary->start_date', '<=', $endYmd)
                            ->where('properties->commit_summary->end_date', '>=', $startYmd)
                            ->when(
                                $shiftId > 0,
                                fn ($query) => $query->whereJsonContains('properties->shift_ids', $shiftId)
                            )
                            ->when(
                                $craftId > 0,
                                fn ($query) => $query->whereJsonContains('properties->craft_ids', $craftId)
                            );
                    });
                });
            })
            ->when($search !== '', function ($q) use ($search): void {
                // Groß-/Kleinschreibung bewusst ignorieren (LOWER auf beiden Seiten), Teiltreffer über
                // LIKE %...%. Spalten explizit mit activity_log. qualifizieren — beim
                // sort=shift_day-Join hat auch shifts eine Spalte "description".
                $like = '%' . mb_strtolower($search) . '%';
                $q->where(function ($inner) use ($like): void {
                    $inner->whereRaw('LOWER(activity_log.description) LIKE ?', [$like])
                        // Namen/Werte stecken in den translation_key_placeholder_values (JSON in properties)
                        ->orWhereRaw('LOWER(activity_log.properties) LIKE ?', [$like])
                        // Verursacher (Planer*in), inkl. "Vorname Nachname"
                        ->orWhereHasMorph(
                            'causer',
                            [User::class],
                            function ($c) use ($like): void {
                                $c->whereRaw('LOWER(first_name) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                                    ->orWhereRaw(
                                        "LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?",
                                        [$like]
                                    );
                            }
                        );
                });
            })
            ->with(['causer'])
            ->when($filters['sort_by_shift_day'], function ($q): void {
                // Nach Schichttag sortieren: über die (auch soft-deleted) Schicht-Row, mit Fallback auf
                // Snapshot-Startdatum (force-gelöscht) bzw. Zeitraum-Beginn des Sammel-Eintrags.
                // select(activity_log.*) verhindert Spaltenkollisionen durch den Join.
                $q->leftJoin('shifts', 'shifts.id', '=', 'activity_log.subject_id')
                    ->select('activity_log.*')
                    ->orderByRaw(
                        'COALESCE(shifts.start_date, '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.shift_snapshot.start_date")), '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.commit_summary.start_date"))'
                        . ') DESC'
                    )
                    ->orderByDesc('activity_log.created_at')
                    ->orderByDesc('activity_log.id');
            }, function ($q): void {
                $q->orderByDesc('activity_log.created_at')
                    ->orderByDesc('activity_log.id');
            });
    }
}
