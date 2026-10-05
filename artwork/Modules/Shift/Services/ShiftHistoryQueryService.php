<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
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
    /** Reichweite des Personenfilters: nur Vorgänge AN der Person (Zuweisung, Entfernung, Zu-/Absage). */
    public const PERSON_SCOPE_SUBJECT = 'subject';
    /** … plus ALLE Einträge der Schichten, in denen die Person eingeplant ist/war (Default). */
    public const PERSON_SCOPE_ASSIGNED = 'assigned';
    /** … plus Einträge, die die Person selbst ausgeführt hat (nur Nutzer*innen als Verursacher). */
    public const PERSON_SCOPE_CAUSER = 'causer';

    public const PERSON_SCOPES = [
        self::PERSON_SCOPE_SUBJECT,
        self::PERSON_SCOPE_ASSIGNED,
        self::PERSON_SCOPE_CAUSER,
    ];

    /** Kurzform aus dem Frontend → Model-Klasse (identisch zu employable_type in shift_workers). */
    public const PERSON_TYPES = [
        'user' => User::class,
        'freelancer' => Freelancer::class,
        'service_provider' => ServiceProvider::class,
    ];

    /** Gründe, warum ein Eintrag bei aktivem Personenfilter angezeigt wird (match_reasons). */
    public const REASON_SUBJECT = 'subject';
    public const REASON_ASSIGNED = 'assigned';
    public const REASON_CAUSER = 'causer';

    /**
     * Filter aus Query-Parametern (craftId, shiftId, start_date, end_date, search, sort, person_type,
     * person_id, person_scope).
     * Zeitraum über ExportPeriodLimit::resolveBounds(): ohne Angabe aktueller Monat, eine Grenze →
     * höchstens ein Jahr ab/bis dahin; länger als ein Jahr → ValidationException (422).
     *
     * @param array<string, mixed> $params
     * @return array{
     *     craft_id: int, shift_id: int, start_date: Carbon, end_date: Carbon,
     *     start_ymd: string, end_ymd: string, search: string, sort_by_shift_day: bool,
     *     person_type: ?string, person_id: int, person_scope: string
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
            'person_type' => self::PERSON_TYPES[(string) ($params['person_type'] ?? '')] ?? null,
            'person_id' => max(0, (int) ($params['person_id'] ?? 0)),
            'person_scope' => in_array($params['person_scope'] ?? null, self::PERSON_SCOPES, true)
                ? (string) $params['person_scope']
                : self::PERSON_SCOPE_ASSIGNED,
        ];
    }

    /**
     * Löst den Personenfilter auf: Model-Klasse, ID und die Namensschreibweisen, unter denen die Person
     * in Alt-Einträgen vorkommt (Platzhalterwerte, affected_workers speichern nur Namen, keine IDs).
     * Null, wenn kein (gültiger) Personenfilter gesetzt ist oder die Person nicht existiert.
     *
     * @param array{person_type: ?string, person_id: int, person_scope: string} $filters
     * @return array{type: string, id: int, scope: string, names: array<int, string>, name: string}|null
     */
    public function resolvePerson(array $filters): ?array
    {
        $type = $filters['person_type'];
        $id = $filters['person_id'];
        if ($type === null || $id <= 0) {
            return null;
        }

        $query = $type::query();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($type), true)) {
            $query->withTrashed();
        }
        /** @var User|Freelancer|ServiceProvider|null $model */
        $model = $query->find($id);
        if ($model === null) {
            return null;
        }

        $names = [];
        if ($model instanceof ServiceProvider) {
            $names[] = trim((string) $model->provider_name);
        } else {
            $first = trim((string) ($model->first_name ?? ''));
            $last = trim((string) ($model->last_name ?? ''));
            $names[] = trim($first . ' ' . $last);
            // Alt-Einträge einzelner Log-Stellen nutzen die Schreibweise "Nachname, Vorname"
            if ($first !== '' && $last !== '') {
                $names[] = $last . ', ' . $first;
            }
        }
        $names = array_values(array_unique(array_filter($names, fn (string $n) => $n !== '')));

        return [
            'type' => $type,
            'id' => $id,
            'scope' => $filters['person_scope'],
            'names' => $names,
            'name' => $names[0] ?? '',
        ];
    }

    /**
     * Schichten (aus $matchedShiftIds), in denen die Person eingeplant ist oder war — inkl. soft-gelöschter
     * Pivots, damit auch entfernte Zuweisungen den Schichtbezug behalten. Per DB-Kaskade (Schicht-Löschung)
     * verschwundene Pivots sind hier nicht mehr sichtbar; solche Einträge greifen über affected_workers
     * (Namen) im Lösch-Eintrag.
     *
     * @param array{type: string, id: int} $person
     * @param array<int, int> $matchedShiftIds
     * @return array<int, int>
     */
    public function personShiftIds(array $person, array $matchedShiftIds): array
    {
        if ($matchedShiftIds === []) {
            return [];
        }

        return ShiftWorker::withTrashed()
            ->where('employable_type', $person['type'])
            ->where('employable_id', $person['id'])
            ->whereIn('shift_id', $matchedShiftIds)
            ->distinct()
            ->pluck('shift_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Gründe, warum ein Eintrag bei aktivem Personenfilter erscheint — für die Bezugs-Chips im Modal und die
     * Spalte "Bezug" im Export. Reihenfolge: subject → assigned → causer. Leer ohne Personenfilter.
     *
     * @param array{type: string, id: int, scope: string, names: array<int, string>} $person
     * @param array<int, int> $personShiftIds
     * @return array<int, string>
     */
    public function matchReasons(Activity $log, array $person, array $personShiftIds): array
    {
        $properties = $log->properties;
        $properties = $properties instanceof \Illuminate\Support\Collection
            ? $properties->all()
            : (array) $properties;

        $reasons = [];

        $placeholders = array_map('strval', (array) ($properties['translation_key_placeholder_values'] ?? []));
        $affected = array_map('strval', (array) ($properties['affected_workers'] ?? []));
        $employableMatch = ($properties['employable_type'] ?? null) === $person['type']
            && (int) ($properties['employable_id'] ?? 0) === $person['id'];
        $nameMatch = array_intersect($person['names'], array_merge($placeholders, $affected)) !== [];
        if ($employableMatch || $nameMatch) {
            $reasons[] = self::REASON_SUBJECT;
        }

        if ($person['scope'] !== self::PERSON_SCOPE_SUBJECT) {
            $shiftIds = $log->subject_id !== null
                ? [(int) $log->subject_id]
                : array_map('intval', (array) ($properties['shift_ids'] ?? []));
            if (array_intersect($shiftIds, $personShiftIds) !== []) {
                $reasons[] = self::REASON_ASSIGNED;
            }
        }

        if (
            $person['scope'] === self::PERSON_SCOPE_CAUSER
            && $person['type'] === User::class
            && $log->causer_type === User::class
            && (int) $log->causer_id === $person['id']
        ) {
            $reasons[] = self::REASON_CAUSER;
        }

        return $reasons;
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
     * @param array{type: string, id: int, scope: string, names: array<int, string>}|null $person
     *     aufgelöster Personenfilter (resolvePerson), null = kein Personenfilter
     * @param array<int, int> $personShiftIds Schichten der Person (personShiftIds), nur mit $person
     */
    public function activityQuery(
        array $filters,
        array $matchedShiftIds,
        ?array $person = null,
        array $personShiftIds = []
    ): Builder {
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
            ->when($person !== null, function ($q) use ($person, $personShiftIds): void {
                $this->applyPersonFilter($q, $person, $personShiftIds);
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

    /**
     * Personenfilter (siehe PERSON_SCOPE_*): Vorgänge an der Person über exakten Namens-Treffer in den
     * Platzhalterwerten / affected_workers (Alt-Einträge kennen keine IDs) oder über employable_type/-id
     * (neue Zuweisungs-Einträge); je nach Reichweite zusätzlich alle Einträge ihrer Schichten (inkl.
     * Sammel-Festschreibungen, die eine ihrer Schichten enthalten) und die von ihr ausgeführten Vorgänge.
     *
     * @param array{type: string, id: int, scope: string, names: array<int, string>} $person
     * @param array<int, int> $personShiftIds
     */
    private function applyPersonFilter(Builder $query, array $person, array $personShiftIds): void
    {
        $query->where(function ($outer) use ($person, $personShiftIds): void {
            $outer->where(function ($subject) use ($person): void {
                $subject->where(function ($byId) use ($person): void {
                    $byId->where('properties->employable_type', $person['type'])
                        ->where('properties->employable_id', $person['id']);
                });
                foreach ($person['names'] as $name) {
                    $subject->orWhereJsonContains('properties->translation_key_placeholder_values', $name)
                        ->orWhereJsonContains('properties->affected_workers', $name);
                }
            });

            if ($person['scope'] !== self::PERSON_SCOPE_SUBJECT && $personShiftIds !== []) {
                $outer->orWhere(function ($assigned) use ($personShiftIds): void {
                    $assigned->where('subject_type', Shift::class)
                        ->whereIn('subject_id', $personShiftIds);
                });
                // Sammel-Einträge (Festschreibung KW/Zeitraum) ohne Subject: enthalten shift_ids
                $outer->orWhere(function ($summary) use ($personShiftIds): void {
                    $summary->whereNull('subject_id');
                    $summary->where(function ($anyShift) use ($personShiftIds): void {
                        foreach (array_slice($personShiftIds, 0, 500) as $shiftId) {
                            $anyShift->orWhereJsonContains('properties->shift_ids', $shiftId);
                        }
                    });
                });
            }

            if ($person['scope'] === self::PERSON_SCOPE_CAUSER && $person['type'] === User::class) {
                $outer->orWhere(function ($caused) use ($person): void {
                    $caused->where('causer_type', User::class)->where('causer_id', $person['id']);
                });
            }
        });
    }
}
