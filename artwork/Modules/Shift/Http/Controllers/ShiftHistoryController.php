<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Serializers\ShiftHistorySerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ShiftHistoryController
{
    public function __construct(private readonly ShiftHistorySerializer $serializer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $craftId = max(0, $request->integer('craftId'));
        $shiftId = max(0, $request->integer('shiftId'));

        // Zeitraum (Default: aktueller Monat)
        $startParam = $request->query('start_date');
        $endParam   = $request->query('end_date');

        $now = Carbon::now(config('app.timezone', 'Europe/Berlin'));

        $startDate = $startParam
            ? Carbon::parse($startParam, config('app.timezone', 'Europe/Berlin'))->startOfDay()
            : $now->copy()->startOfMonth()->startOfDay();

        $endDate = $endParam
            ? Carbon::parse($endParam, config('app.timezone', 'Europe/Berlin'))->endOfDay()
            : $now->copy()->endOfMonth()->endOfDay();

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(200, $perPage));

        $page = max(1, (int) $request->query('page', 1));
        // Die vollständige Shift-Liste (inkl. Relationen) ändert sich beim Paginieren nicht
        // und wird daher nur für die erste Seite geladen & serialisiert. Beim "Mehr laden"
        // brauchen wir nur die IDs, um die passenden Activities zu filtern.
        $loadShiftDetails = $page === 1;

        // Freitext-Suche (z.B. nach einem Mitarbeiternamen). Muss serverseitig erfolgen,
        // damit auch Treffer auf späteren Seiten gefunden werden – clientseitiges Filtern
        // einer einzelnen Seite würde passende Einträge sonst verbergen.
        $search = trim((string) $request->query('search', ''));

        // Sortierung: 'shift_day' = nach dem Tag der zugehörigen Schicht (damit die
        // Paginierung vollständige Schichttage von oben befüllt), sonst nach Änderungsdatum.
        $sortByShiftDay = $request->query('sort') === 'shift_day';

        $startYmd = $startDate->toDateString();
        $endYmd   = $endDate->toDateString();

        // Shifts im Zeitraum (Overlaps!) – bewusst inkl. soft-deleted (withTrashed):
        // Eine gelöschte Schicht soll mit ihrem KOMPLETTEN Verlauf sichtbar bleiben,
        // auch mit Einträgen, die VOR diesem Feature entstanden sind (ohne Snapshot).
        // Solange die Row noch existiert (auch soft-deleted), liefert sie das Schicht-
        // Start-Datum für den Zeitraum-Filter.
        $shiftQuery = Shift::withTrashed()
            ->when($craftId > 0, fn ($q) => $q->where('craft_id', $craftId))
            ->startAndEndDateOverlap($startYmd, $endYmd);

        if ($loadShiftDetails) {
            // Avoid serializing expensive Shift appends for the filter dropdown.
            $shifts = (clone $shiftQuery)
                ->select([
                    'id',
                    'craft_id',
                    'start_date',
                    'end_date',
                    'start',
                    'end',
                    'description',
                    'room_id',
                    'project_id',
                    'is_committed',
                    'in_workflow',
                    'deleted_at',
                ])
                ->with([
                    'room:id,name',
                    'project:id,name',
                    'craft:id,name,abbreviation',
                ])
                ->orderBy('start_date')
                ->orderBy('start')
                ->get()
                ->map($this->serializer->serializeShift(...))
                ->values();

            $shiftIds = $shiftId > 0
                ? $shifts->where('id', $shiftId)->pluck('id')->all()
                : $shifts->pluck('id')->all();
        } else {
            // Folgeseiten: nur die IDs, um die Activities zu filtern.
            $shifts = null;
            $shiftIds = (clone $shiftQuery)
                ->when($shiftId > 0, fn ($query) => $query->whereKey($shiftId))
                ->pluck('id')
                ->all();
        }

        // Force-gelöschte Schichten (Row physisch weg) über den im Log gespeicherten
        // Snapshot wieder einfangen: deren Einträge tragen properties->shift_snapshot
        // (inkl. Schicht-Start-Datum), das das Löschen überlebt.
        //
        // Performance: Diese Abfrage wertet einen JSON-Pfad (properties->shift_snapshot->
        // start_date) ohne Index aus – auf großen activity_log-Tabellen teuer. Wir grenzen
        // sie daher hart ein:
        //   - event='deleted': force-gelöschte Schichten loggen IMMER ein deleted-Event mit
        //     Snapshot (forceDelete ruft intern delete()); andere Events brauchen wir hier
        //     nicht, um die subject_id einzufangen → deutlich weniger JSON-Auswertungen.
        //   - whereNotIn($shiftIds): lebende/soft-deleted Schichten sind bereits abgedeckt;
        //     hier interessieren nur Schichten ohne Row.
        $snapshotShiftIds = Activity::query()
            ->where('log_name', 'shift')
            ->where('subject_type', Shift::class)
            ->where('event', 'deleted')
            ->when($shiftId > 0, fn ($query) => $query->where('subject_id', $shiftId))
            ->when(!empty($shiftIds), fn ($q) => $q->whereNotIn('subject_id', $shiftIds))
            ->whereBetween('properties->shift_snapshot->start_date', [$startYmd, $endYmd])
            ->when($craftId > 0, fn ($q) => $q->where('properties->craft_id', $craftId))
            ->distinct()
            ->pluck('subject_id')
            ->all();

        // Vereinigte Menge: alle Schichten (lebend, soft-deleted, force-deleted-mit-Snapshot),
        // deren START im Zeitraum liegt. Für genau diese Schichten zeigen wir ALLE Activities –
        // damit der komplette Verlauf inkl. Lösch-Eintrag erscheint, unabhängig vom Fortbestand.
        $matchedShiftIds = array_values(array_unique(array_merge($shiftIds, $snapshotShiftIds)));
        $includeCommitSummaries = $shiftId === 0 || in_array($shiftId, $matchedShiftIds, true);

        // Activity Logs (Spatie activity_log) – über die Schicht-Zugehörigkeit, NICHT über
        // den Fortbestand der Schicht. So bleiben Einträge gelöschter Schichten sichtbar.
        // Zusätzlich: Sammel-Einträge (Festschreibung einer KW / eines Zeitraums) haben
        // bewusst KEIN Subject — sie werden über den in properties->commit_summary
        // gespeicherten Zeitraum (Überlappung) und optional die craft_ids eingesammelt.
        $paginator = Activity::query()
            ->where('log_name', 'shift')
            // Zu-/Absagen der Mitarbeitenden werden immer geloggt, aber nur
            // angezeigt, wenn das Setting es erlaubt. NULL-safe filtern:
            // "event NOT IN (...)" würde Einträge ohne event-Wert mit verwerfen.
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
                // Groß-/Kleinschreibung bewusst ignorieren (LOWER auf beiden Seiten),
                // damit z.B. "jannik" auch "Jannik Müller" findet – unabhängig von der
                // Spalten-Collation. Teiltreffer über LIKE %...%.
                $like = '%' . mb_strtolower($search) . '%';
                $q->where(function ($inner) use ($like): void {
                    // Beschreibung des Log-Eintrags. Spalten explizit mit activity_log.
                    // qualifizieren – beim sort=shift_day-Join hat auch shifts eine Spalte
                    // "description" → sonst "Column 'description' is ambiguous".
                    $inner->whereRaw('LOWER(activity_log.description) LIKE ?', [$like])
                        // Namen/Werte stecken in den translation_key_placeholder_values (JSON in properties),
                        // z.B. der zugewiesene Mitarbeitername.
                        ->orWhereRaw('LOWER(activity_log.properties) LIKE ?', [$like])
                        // Verursacher (Planer:in), der die Änderung ausgelöst hat
                        ->orWhereHasMorph(
                            'causer',
                            [\Artwork\Modules\User\Models\User::class],
                            function ($c) use ($like): void {
                                $c->whereRaw('LOWER(first_name) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                                    // Vollständigen Namen ("Vorname Nachname") matchen –
                                    // sonst findet die Suche nach dem kompletten Namen den
                                    // Verursacher nie (Felder werden einzeln verglichen).
                                    ->orWhereRaw(
                                        "LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?",
                                        [$like]
                                    );
                            }
                        );
                });
            })
            ->with([
                // Causer: bei dir ist das offenbar ein User-Objekt mit first/last/full_name/type/profile_photo_url
                'causer',
            ])
            ->when($sortByShiftDay, function ($q): void {
                // Nach Schichttag sortieren: über die (auch soft-deleted) Schicht-Row, mit
                // Fallback auf das im Snapshot gespeicherte Start-Datum für force-gelöschte
                // Schichten (keine Row mehr). Innerhalb eines Tages weiter neueste Änderung
                // zuerst. select(activity_log.*) verhindert Spaltenkollisionen durch den Join.
                $q->leftJoin('shifts', 'shifts.id', '=', 'activity_log.subject_id')
                    ->select('activity_log.*')
                    ->orderByRaw(
                        'COALESCE(shifts.start_date, '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.shift_snapshot.start_date")), '
                        // Sammel-Einträge (Festschreibung) haben kein Subject/Snapshot —
                        // ihr Zeitraum-Beginn sortiert sie in den richtigen Schichttag ein.
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.commit_summary.start_date"))'
                        . ') DESC'
                    )
                    ->orderByDesc('activity_log.created_at')
                    ->orderByDesc('activity_log.id');
            }, function ($q): void {
                $q->orderByDesc('activity_log.created_at')
                    ->orderByDesc('activity_log.id');
            })
            ->paginate($perPage);

        $response = [
            'logs'   => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ],
            'range'  => [
                'start_date' => $startDate->toDateString(),
                'end_date'   => $endDate->toDateString(),
            ],
        ];

        // Shift-Liste nur auf der ersten Seite mitschicken (siehe $loadShiftDetails).
        if ($loadShiftDetails) {
            $response['shifts'] = $shifts;
        }

        return response()->json($response);
    }
}
