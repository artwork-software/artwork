<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Project\Models\ProjectDayAssignment;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanComment;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Support\ExportPeriodLimit;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
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

    /**
     * Tageseinträge einer Person (Abwesenheit, Verfügbarkeit, Notiz, individuelle Zeit, Ausgleichstag,
     * Projektzuordnung) — erscheinen im Verlauf als eigene Vorgänge (siehe LogsShiftPlanDayActivity).
     */
    public const DAY_ENTRY_SUBJECTS = [
        Vacation::class,
        Availability::class,
        ShiftPlanComment::class,
        IndividualTime::class,
        CompensationDayOff::class,
        ProjectDayAssignment::class,
    ];

    /**
     * Tageseinträge, die über den Log-Namen erkannt werden: Projektzuordnungen (teils ohne Subject, Massenanlage
     * per insert) und Tagesdienste (Subject ist der Dienst, nicht die Zuweisung)
     */
    public const DAY_ENTRY_LOG_NAMES = ['project_day_assignment', 'day_service'];

    /**
     * Log-Name + Subject je Tageseintrag-Art, damit die Abfrage den Index (log_name, subject_type) nutzt statt
     * activity_log komplett zu lesen. Enthält die Altformate (Vacation-Änderungen im Log „default“,
     * Availability-Änderungen im Log „vacation“). Subject null = jedes Subject.
     *
     * @var array<int, array{0: string, 1: ?class-string}>
     */
    private const DAY_ENTRY_LOG_SUBJECTS = [
        ['vacation', Vacation::class],
        ['default', Vacation::class],
        ['availability', Availability::class],
        ['vacation', Availability::class],
        ['shift_plan_comment', ShiftPlanComment::class],
        ['individual_time', IndividualTime::class],
        ['compensation_day_off', CompensationDayOff::class],
        ['project_day_assignment', null],
        ['day_service', null],
    ];

    /**
     * Morph-Spalten der Person in properties.attributes/old — für Einträge von vor LogsShiftPlanDayActivity,
     * die noch kein employable_type/employable_id tragen.
     */
    private const DAY_ENTRY_PERSON_PREFIXES = ['vacationer', 'available'];

    /**
     * Erster Tag eines Tageseintrags: worker_day, sonst das geloggte Datum (Alt-Einträge). NULLIF, weil
     * JSON_UNQUOTE ein gespeichertes JSON-null als Text "null" liefert, der jedes Datum „überholt“.
     */
    private const DAY_ENTRY_DATE_SQL = "COALESCE("
        . "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, '$.worker_day')), 'null'), "
        . "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, '$.attributes.date')), 'null'), "
        . "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, '$.old.date')), 'null'))";

    /** Letzter Tag eines mehrtägigen Eintrags (individuelle Zeit, Projektzuordnung), sonst der erste */
    private const DAY_ENTRY_END_DATE_SQL = "COALESCE("
        . "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, '$.worker_day_end')), 'null'), "
        . self::DAY_ENTRY_DATE_SQL . ")";

    /** Ist der Activity-Eintrag ein Tageseintrag? (Subject oder Log-Name) */
    public static function isDayEntryLog(Activity $log): bool
    {
        return in_array($log->subject_type, self::DAY_ENTRY_SUBJECTS, true)
            || in_array($log->log_name, self::DAY_ENTRY_LOG_NAMES, true);
    }

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
        $isDayEntry = self::isDayEntryLog($log);
        // Ältere Tageseinträge ohne employable_*: Person aus den geloggten Morph-Spalten
        if ($isDayEntry && ! $employableMatch) {
            foreach (['attributes', 'old'] as $section) {
                foreach (self::DAY_ENTRY_PERSON_PREFIXES as $prefix) {
                    $data = (array) ($properties[$section] ?? []);
                    if (
                        ($data[$prefix . '_type'] ?? null) === $person['type']
                        && (int) ($data[$prefix . '_id'] ?? 0) === $person['id']
                    ) {
                        $employableMatch = true;
                    }
                }
            }
        }
        $nameMatch = array_intersect($person['names'], array_merge($placeholders, $affected)) !== [];
        if ($employableMatch || $nameMatch) {
            $reasons[] = self::REASON_SUBJECT;
        }

        if ($person['scope'] !== self::PERSON_SCOPE_SUBJECT && ! $isDayEntry) {
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
        $shiftId = $filters['shift_id'];
        $search = $filters['search'];
        $includeCommitSummaries = $shiftId === 0 || in_array($shiftId, $matchedShiftIds, true);

        // Tageseinträge (Abwesenheit, Verfügbarkeit, Notiz, …) ohne Einzelschicht-Filter: mit Personenfilter
        // die der Person, sonst alle im Zeitraum (mit Gewerk-Filter die der Personen des Gewerks)
        $includeDayEntries = $shiftId === 0;
        $dayEntryPersons = $includeDayEntries && $person === null && $filters['craft_id'] > 0
            ? $this->craftPersonIdsByType($filters['craft_id'], $matchedShiftIds)
            : null;

        return Activity::query()
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
                $filters,
                $matchedShiftIds,
                $includeCommitSummaries,
                $includeDayEntries,
                $person,
                $dayEntryPersons
            ): void {
                $query->where(fn ($shiftLog) => $this->whereShiftLogInScope(
                    $shiftLog,
                    $filters,
                    $matchedShiftIds,
                    $includeCommitSummaries
                ))->when($includeDayEntries, function ($query) use ($person, $filters, $dayEntryPersons): void {
                    $query->orWhere(function ($dayEntry) use ($person, $filters, $dayEntryPersons): void {
                        if ($person !== null) {
                            $this->whereDayEntryOfPerson($dayEntry, $person);
                        } elseif ($dayEntryPersons !== null) {
                            $this->whereDayEntryOfPersons($dayEntry, $dayEntryPersons);
                        } else {
                            $this->whereDayEntry($dayEntry);
                        }
                        // Zeitraum-Überlappung: mehrtägige Einträge zählen an jedem ihrer Tage
                        $dayEntry->whereRaw(self::DAY_ENTRY_DATE_SQL . ' <= ?', [$filters['end_ymd']])
                            ->whereRaw(self::DAY_ENTRY_END_DATE_SQL . ' >= ?', [$filters['start_ymd']]);
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
                // Join nur für Schicht-Subjects: Tageseinträge haben eine andere subject_id-Bedeutung
                $q->leftJoin('shifts', function ($join): void {
                    $join->on('shifts.id', '=', 'activity_log.subject_id')
                        ->where('activity_log.subject_type', '=', Shift::class);
                })
                    ->select('activity_log.*')
                    ->orderByRaw(
                        'COALESCE(shifts.start_date, '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.shift_snapshot.start_date")), '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.commit_summary.start_date")), '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.worker_day")), '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.attributes.date")), '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(activity_log.properties, "$.old.date"))'
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

            // Tageseinträge der Person (Alt-Einträge ohne employable_* über attributes/old)
            $outer->orWhere(fn ($dayEntry) => $this->whereDayEntryOfPerson($dayEntry, $person));

            if ($person['scope'] === self::PERSON_SCOPE_CAUSER && $person['type'] === User::class) {
                $outer->orWhere(function ($caused) use ($person): void {
                    $caused->where('causer_type', User::class)->where('causer_id', $person['id']);
                });
            }
        });
    }

    /**
     * Einträge des Schicht-Logs im Filterbereich: Vorgänge an den Schichten plus Sammel-Einträge
     * (Festschreibung KW/Zeitraum) ohne Subject, die den Zeitraum/die Schicht/das Gewerk berühren.
     *
     * @param array{craft_id: int, shift_id: int, start_ymd: string, end_ymd: string} $filters
     * @param array<int, int> $matchedShiftIds
     */
    private function whereShiftLogInScope(
        Builder $query,
        array $filters,
        array $matchedShiftIds,
        bool $includeCommitSummaries
    ): void {
        $craftId = $filters['craft_id'];
        $shiftId = $filters['shift_id'];
        $startYmd = $filters['start_ymd'];
        $endYmd = $filters['end_ymd'];

        $query->where('log_name', 'shift')->where(function ($query) use (
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
        });
    }

    /** Tageseintrag irgendeiner Person (Subject oder Log-Name, siehe isDayEntryLog) */
    private function whereDayEntry(Builder $query): void
    {
        $query->where(function ($kind): void {
            foreach (self::DAY_ENTRY_LOG_SUBJECTS as [$logName, $subjectType]) {
                $kind->orWhere(fn ($pair) => $pair
                    ->where('activity_log.log_name', $logName)
                    ->when($subjectType !== null, fn ($q) => $q->where('activity_log.subject_type', $subjectType)));
            }
        });
    }

    /**
     * Tageseintrag der Person: über employable_* (LogsShiftPlanDayActivity) oder bei älteren Einträgen
     * über die geloggten Morph-Spalten in attributes/old.
     *
     * @param array{type: string, id: int} $person
     */
    private function whereDayEntryOfPerson(Builder $query, array $person): void
    {
        $this->whereDayEntryOfPersons($query, [$person['type'] => [$person['id']]]);
    }

    /**
     * Tageseinträge mehrerer Personen (Gewerk-Filter ohne Personenfilter).
     *
     * @param array<class-string, array<int, int>> $idsByType
     */
    private function whereDayEntryOfPersons(Builder $query, array $idsByType): void
    {
        $this->whereDayEntry($query);
        $query->where(function ($personMatch) use ($idsByType): void {
            // Ohne Personen: nichts finden statt alles
            $personMatch->whereRaw('1 = 0');
            foreach ($idsByType as $type => $ids) {
                if ($ids === []) {
                    continue;
                }
                $personMatch->orWhere(function ($byEmployable) use ($type, $ids): void {
                    $byEmployable->where('properties->employable_type', $type)
                        ->whereIn('properties->employable_id', $ids);
                });
                foreach (['attributes', 'old'] as $section) {
                    foreach (self::DAY_ENTRY_PERSON_PREFIXES as $prefix) {
                        $personMatch->orWhere(function ($legacy) use ($type, $ids, $section, $prefix): void {
                            $legacy->where("properties->{$section}->{$prefix}_type", $type)
                                ->whereIn("properties->{$section}->{$prefix}_id", $ids);
                        });
                    }
                }
            }
        });
    }

    /**
     * Personen eines Gewerks für den Verlauf ohne Personenfilter: Mitglieder plus alle, die auf Schichten
     * im Filterbereich eingeteilt sind/waren.
     *
     * @param array<int, int> $matchedShiftIds
     * @return array<class-string, array<int, int>>
     */
    private function craftPersonIdsByType(int $craftId, array $matchedShiftIds): array
    {
        $craft = Craft::query()->find($craftId);
        $idsByType = [
            User::class => $craft?->users()->pluck('users.id')->all() ?? [],
            Freelancer::class => $craft?->freelancers()->pluck('freelancers.id')->all() ?? [],
            ServiceProvider::class => $craft?->serviceProviders()->pluck('service_providers.id')->all() ?? [],
        ];

        if ($matchedShiftIds !== []) {
            ShiftWorker::withTrashed()
                ->whereIn('shift_id', $matchedShiftIds)
                ->distinct()
                ->get(['employable_type', 'employable_id'])
                ->each(function (ShiftWorker $worker) use (&$idsByType): void {
                    if (array_key_exists($worker->employable_type, $idsByType)) {
                        $idsByType[$worker->employable_type][] = (int) $worker->employable_id;
                    }
                });
        }

        return array_map(
            fn (array $ids) => array_values(array_unique(array_map('intval', $ids))),
            $idsByType
        );
    }
}
