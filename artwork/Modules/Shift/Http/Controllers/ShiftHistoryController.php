<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ShiftHistoryController
{
    public function index(Request $request): JsonResponse
    {
        $craftId = $request->query('craftId');
        // craftId=0 or null means "all crafts"
        $craftId = $craftId !== null ? (int) $craftId : 0;

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
            // Erste Seite: volle Shift-Liste inkl. Relationen für die Filter-Dropdowns im Frontend.
            // deleted_at mitgeben, damit das Frontend gelöschte Schichten kennzeichnen kann.
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
                ->get();

            $shiftIds = $shifts->pluck('id')->all();
        } else {
            // Folgeseiten: nur die IDs, um die Activities zu filtern.
            $shifts = null;
            $shiftIds = (clone $shiftQuery)->pluck('id')->all();
        }

        // Force-gelöschte Schichten (Row physisch weg) über den im Log gespeicherten
        // Snapshot wieder einfangen: deren Einträge tragen properties->shift_snapshot
        // (inkl. Schicht-Start-Datum), das das Löschen überlebt.
        $snapshotShiftIds = Activity::query()
            ->where('log_name', 'shift')
            ->where('subject_type', Shift::class)
            ->whereBetween('properties->shift_snapshot->start_date', [$startYmd, $endYmd])
            ->when($craftId > 0, fn ($q) => $q->where('properties->craft_id', $craftId))
            ->distinct()
            ->pluck('subject_id')
            ->all();

        // Vereinigte Menge: alle Schichten (lebend, soft-deleted, force-deleted-mit-Snapshot),
        // deren START im Zeitraum liegt. Für genau diese Schichten zeigen wir ALLE Activities –
        // damit der komplette Verlauf inkl. Lösch-Eintrag erscheint, unabhängig vom Fortbestand.
        $matchedShiftIds = array_values(array_unique(array_merge($shiftIds, $snapshotShiftIds)));

        if (empty($matchedShiftIds)) {
            return response()->json([
                'shifts' => $shifts ?? [],
                'logs'   => [
                    'data' => [],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => $perPage, 'total' => 0],
                ],
                'range'  => [
                    'start_date' => $startYmd,
                    'end_date'   => $endYmd,
                ],
            ]);
        }

        // Activity Logs (Spatie activity_log) – über die Schicht-Zugehörigkeit, NICHT über
        // den Fortbestand der Schicht. So bleiben Einträge gelöschter Schichten sichtbar.
        $paginator = Activity::query()
            ->where('log_name', 'shift')
            ->where('subject_type', Shift::class)
            ->whereIn('subject_id', $matchedShiftIds)
            ->when($search !== '', function ($q) use ($search): void {
                // Groß-/Kleinschreibung bewusst ignorieren (LOWER auf beiden Seiten),
                // damit z.B. "jannik" auch "Jannik Müller" findet – unabhängig von der
                // Spalten-Collation. Teiltreffer über LIKE %...%.
                $like = '%' . mb_strtolower($search) . '%';
                $q->where(function ($inner) use ($like): void {
                    // Beschreibung des Log-Eintrags
                    $inner->whereRaw('LOWER(description) LIKE ?', [$like])
                        // Namen/Werte stecken in den translation_key_placeholder_values (JSON in properties),
                        // z.B. der zugewiesene Mitarbeitername.
                        ->orWhereRaw('LOWER(properties) LIKE ?', [$like])
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
            ->orderByDesc('created_at')
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
