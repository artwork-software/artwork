<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

/**
 * Wochenstatus je Gewerk × ISO-Kalenderwoche für die Seite „Wochenstatus".
 *
 * Status-Regeln (Priorität von oben nach unten):
 *  - none       keine Schicht des Gewerks in der KW
 *  - committed  alle Schichten festgeschrieben
 *  - requested  eine Freigabe-Anfrage (ShiftPlanRequest) ist ausstehend (pending)
 *  - rejected   die letzte Anfrage wurde abgelehnt und nichts wurde neu angefragt
 *  - partial    ein Teil der Schichten ist festgeschrieben
 *  - open       Schichten vorhanden, nichts festgeschrieben, keine Anfrage
 *
 * Alle Datenquellen (Schichten, Schichtplätze, Zuweisungen, Anfragen, Änderungen,
 * Verstöße) werden je Zeitraum in genau EINER Abfrage geladen und in PHP gruppiert —
 * das Query-Budget ist unabhängig von der Anzahl Gewerke und Wochen (max. 6 Abfragen).
 */
class ShiftWeekStatusService
{
    public const MAX_WEEKS = 26;

    public const DUE_SOON_DAYS = 3;

    public const STATUS_NONE = 'none';
    public const STATUS_OPEN = 'open';
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_COMMITTED = 'committed';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @param array<int, int> $craftIds Gewerke, für die Zellen berechnet werden (leer → keine Zeilen)
     * @param Carbon|null $today Stichtag für die Frist-Zustände (Tests); Default: heute
     * @return array{
     *     from: string,
     *     to: string,
     *     weeks: array<int, array<string, mixed>>,
     *     rows: array<int, array<string, array<string, mixed>>>,
     *     summary: array<string, array<string, int>>
     * }
     */
    public function compute(Carbon $from, Carbon $to, array $craftIds, ?Carbon $today = null): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $craftIds = array_values(array_unique(array_map('intval', $craftIds)));

        $weeks = $this->weeks($from, $to);
        $weekKeys = array_column($weeks, 'key');

        // Gewerke inkl. Frist-Tage — eine Abfrage, keine Relationen
        $crafts = $craftIds === []
            ? new EloquentCollection()
            : Craft::query()
                ->select(['id', 'commit_request_deadline_days'])
                ->without(['craftShiftPlaner'])
                ->whereIn('id', $craftIds)
                ->get()
                ->keyBy('id');

        // Aggregat je Gewerk × KW
        $cells = [];
        foreach ($craftIds as $craftId) {
            foreach ($weekKeys as $weekKey) {
                $cells[$craftId][$weekKey] = $this->emptyAggregate();
            }
        }

        if ($craftIds !== []) {
            $shifts = $this->loadShifts($from, $to, $craftIds);
            $this->applyShifts($cells, $shifts, $weekKeys);
            $this->applyStaffing($cells, $shifts);
            $this->applyRequests($cells, $craftIds, $weeks);
            $this->applyChanges($cells, $shifts);
            $this->applyViolations($cells, $shifts, $from, $to);
        }

        $rows = [];
        $summary = [];
        foreach ($weeks as $week) {
            $summary[$week['key']] = [
                'crafts_with_shifts' => 0,
                'crafts_committed' => 0,
                'crafts_requested' => 0,
                'open_changes' => 0,
                'open_violations' => 0,
                'required_slots' => 0,
                'staffed_slots' => 0,
                'open_slots' => 0,
            ];
        }

        foreach ($cells as $craftId => $weekCells) {
            $deadlineDays = $crafts->get($craftId)?->commit_request_deadline_days;

            foreach ($weeks as $week) {
                $cell = $this->finalizeCell($weekCells[$week['key']], $week, $deadlineDays, $today);
                $rows[$craftId][$week['key']] = $cell;

                $weekSummary = &$summary[$week['key']];
                if ($cell['status'] !== self::STATUS_NONE) {
                    $weekSummary['crafts_with_shifts']++;
                }
                if ($cell['status'] === self::STATUS_COMMITTED) {
                    $weekSummary['crafts_committed']++;
                }
                if ($cell['status'] === self::STATUS_REQUESTED) {
                    $weekSummary['crafts_requested']++;
                }
                $weekSummary['open_changes'] += $cell['open_changes'];
                $weekSummary['required_slots'] += $cell['required_slots'];
                $weekSummary['staffed_slots'] += $cell['staffed_slots'];
                $weekSummary['open_slots'] += $cell['open_slots'];
                unset($weekSummary);
            }
        }

        // Verstöße in der Summenzeile je KW nur einmal zählen, auch wenn dieselbe Person
        // in mehreren Gewerken der KW eingeplant ist.
        foreach ($cells as $weekCells) {
            foreach ($weekCells as $weekKey => $aggregate) {
                $summary[$weekKey]['_violation_ids'] = ($summary[$weekKey]['_violation_ids'] ?? [])
                    + $aggregate['violation_ids'];
            }
        }
        foreach ($summary as $weekKey => $weekSummary) {
            $summary[$weekKey]['open_violations'] = count($weekSummary['_violation_ids'] ?? []);
            unset($summary[$weekKey]['_violation_ids']);
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'weeks' => $weeks,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * Zeitraum auf Montag–Sonntag runden und auf MAX_WEEKS Wochen deckeln.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function normalizeRange(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $to = $to->copy()->endOfWeek(CarbonInterface::SUNDAY)->startOfDay();

        if ($to->lessThan($from)) {
            $to = $from->copy()->addDays(6);
        }

        $maxTo = $from->copy()->addWeeks(self::MAX_WEEKS)->subDay();
        if ($to->greaterThan($maxTo)) {
            $to = $maxTo;
        }

        return [$from, $to];
    }

    /**
     * Wochenbeschreibungen (ISO-Jahr/-KW, Montag, Sonntag) für den Zeitraum.
     *
     * @return array<int, array{key: string, week_number: int, year: int, monday: string, sunday: string, monday_formatted: string, sunday_formatted: string}>
     */
    public function weeks(Carbon $from, Carbon $to): array
    {
        $weeks = [];
        $cursor = $from->copy()->startOfWeek(CarbonInterface::MONDAY);
        $end = $to->copy()->startOfWeek(CarbonInterface::MONDAY);

        while ($cursor->lessThanOrEqualTo($end)) {
            $sunday = $cursor->copy()->addDays(6);
            $weeks[] = [
                'key' => $this->weekKey($cursor),
                'week_number' => (int) $cursor->format('W'),
                'year' => (int) $cursor->format('o'),
                'monday' => $cursor->toDateString(),
                'sunday' => $sunday->toDateString(),
                'monday_formatted' => $cursor->format('d.m.Y'),
                'sunday_formatted' => $sunday->format('d.m.Y'),
            ];
            $cursor->addWeek();
        }

        return $weeks;
    }

    public function weekKey(CarbonInterface $date): string
    {
        return sprintf('%d-W%02d', (int) $date->format('o'), (int) $date->format('W'));
    }

    public function weekKeyFor(int $year, int $week): string
    {
        return sprintf('%d-W%02d', $year, $week);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAggregate(): array
    {
        return [
            'shifts_total' => 0,
            'shifts_committed' => 0,
            'shifts_in_workflow' => 0,
            'shift_ids' => [],
            'user_ids' => [],
            'required_slots' => 0,
            'staffed_slots' => 0,
            'open_changes' => 0,
            'violation_ids' => [],
            'pending_request' => null,
            'latest_request' => null,
        ];
    }

    /**
     * Schichten des Zeitraums — nur die Spalten, die gebraucht werden, keine Relationen.
     */
    private function loadShifts(Carbon $from, Carbon $to, array $craftIds): EloquentCollection
    {
        return Shift::query()
            ->select(['id', 'craft_id', 'start_date', 'is_committed', 'in_workflow', 'current_request_id'])
            ->whereIn('craft_id', $craftIds)
            ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
            ->get();
    }

    private function applyShifts(array &$cells, EloquentCollection $shifts, array $weekKeys): void
    {
        $weekKeyLookup = array_flip($weekKeys);

        foreach ($shifts as $shift) {
            $craftId = (int) $shift->craft_id;
            $weekKey = $this->weekKey(Carbon::parse($shift->start_date));

            if (! isset($weekKeyLookup[$weekKey]) || ! isset($cells[$craftId][$weekKey])) {
                continue;
            }

            $cell = &$cells[$craftId][$weekKey];
            $cell['shifts_total']++;
            $cell['shift_ids'][] = (int) $shift->id;
            if ($shift->is_committed) {
                $cell['shifts_committed']++;
            }
            if ($shift->in_workflow) {
                $cell['shifts_in_workflow']++;
            }
            unset($cell);
        }
    }

    /**
     * Bedarf = Summe shifts_qualifications.value (> 0), besetzt = reguläre Zuweisungen auf
     * geforderte Funktionen (überbuchte zählen nicht) — identisch zu shiftStaffing.js.
     * Zusätzlich werden die eingeplanten User (für die Verstoß-Zuordnung) je Zelle gesammelt.
     */
    private function applyStaffing(array &$cells, EloquentCollection $shifts): void
    {
        if ($shifts->isEmpty()) {
            return;
        }

        $shiftIds = $shifts->pluck('id')->map(fn ($id) => (int) $id)->all();
        $cellByShift = [];
        foreach ($cells as $craftId => $weekCells) {
            foreach ($weekCells as $weekKey => $aggregate) {
                foreach ($aggregate['shift_ids'] as $shiftId) {
                    $cellByShift[$shiftId] = [$craftId, $weekKey];
                }
            }
        }

        $qualifications = ShiftsQualifications::query()
            ->select(['shift_id', 'shift_qualification_id', 'value'])
            ->whereIn('shift_id', $shiftIds)
            ->get();

        $workers = DB::table('shift_workers')
            ->select(['shift_id', 'shift_qualification_id', 'employable_type', 'employable_id', 'is_overbooked'])
            ->whereIn('shift_id', $shiftIds)
            ->whereNull('deleted_at')
            ->get();

        // [shift_id][qualification_id] => reguläre Zuweisungen
        $workersByShiftQualification = [];
        foreach ($workers as $worker) {
            $shiftId = (int) $worker->shift_id;
            $location = $cellByShift[$shiftId] ?? null;
            if ($location === null) {
                continue;
            }

            if ($worker->employable_type === User::class) {
                [$craftId, $weekKey] = $location;
                $cells[$craftId][$weekKey]['user_ids'][(int) $worker->employable_id] = true;
            }

            if ($worker->is_overbooked) {
                continue;
            }

            $qualificationId = (int) $worker->shift_qualification_id;
            $workersByShiftQualification[$shiftId][$qualificationId] =
                ($workersByShiftQualification[$shiftId][$qualificationId] ?? 0) + 1;
        }

        foreach ($qualifications as $qualification) {
            $value = (int) $qualification->value;
            if ($value <= 0) {
                continue;
            }

            $shiftId = (int) $qualification->shift_id;
            $location = $cellByShift[$shiftId] ?? null;
            if ($location === null) {
                continue;
            }

            [$craftId, $weekKey] = $location;
            $cell = &$cells[$craftId][$weekKey];
            $cell['required_slots'] += $value;
            $cell['staffed_slots'] += $workersByShiftQualification[$shiftId][(int) $qualification->shift_qualification_id] ?? 0;
            unset($cell);
        }
    }

    /**
     * Freigabe-Anfragen aller Gewerke/KWs des Zeitraums in einer Abfrage.
     * Neueste Anfrage (höchste id) je Gewerk/KW = „letzte Anfrage"; pending separat gemerkt.
     */
    private function applyRequests(array &$cells, array $craftIds, array $weeks): void
    {
        $weeksByYear = [];
        foreach ($weeks as $week) {
            $weeksByYear[$week['year']][] = $week['week_number'];
        }

        $requests = ShiftPlanRequest::query()
            ->select([
                'id', 'craft_id', 'week_number', 'year', 'status',
                'requested_by_user_id', 'requested_at', 'created_at', 'reviewed_at', 'review_comment',
            ])
            ->whereIn('craft_id', $craftIds)
            ->where(function ($query) use ($weeksByYear): void {
                foreach ($weeksByYear as $year => $weekNumbers) {
                    $query->orWhere(function ($sub) use ($year, $weekNumbers): void {
                        $sub->where('year', $year)->whereIn('week_number', $weekNumbers);
                    });
                }
            })
            ->orderByDesc('id')
            ->get();

        foreach ($requests as $request) {
            $craftId = (int) $request->craft_id;
            $weekKey = $this->weekKeyFor((int) $request->year, (int) $request->week_number);
            if (! isset($cells[$craftId][$weekKey])) {
                continue;
            }

            $payload = [
                'id' => (int) $request->id,
                'status' => (string) $request->status,
                'requested_by_user_id' => $request->requested_by_user_id !== null
                    ? (int) $request->requested_by_user_id
                    : null,
                'requested_at' => ($request->requested_at ?? $request->created_at)?->toIso8601String(),
                'reviewed_at' => $request->reviewed_at?->toIso8601String(),
                'review_comment' => $request->review_comment,
            ];

            $cell = &$cells[$craftId][$weekKey];
            // orderByDesc('id') → der erste Treffer je Zelle ist die neueste Anfrage
            $cell['latest_request'] ??= $payload;
            if ($request->status === 'pending' && $cell['pending_request'] === null) {
                $cell['pending_request'] = $payload;
            }
            unset($cell);
        }
    }

    /**
     * Unbestätigte Änderungen nach Festschreibung, gezählt je Schicht in SQL, in PHP auf Zellen verteilt.
     */
    private function applyChanges(array &$cells, EloquentCollection $shifts): void
    {
        if ($shifts->isEmpty()) {
            return;
        }

        $shiftIds = $shifts->pluck('id')->map(fn ($id) => (int) $id)->all();

        $changeCounts = CommittedShiftChange::query()
            ->selectRaw('shift_id, COUNT(*) as change_count')
            ->whereNull('acknowledged_at')
            ->whereIn('shift_id', $shiftIds)
            ->groupBy('shift_id')
            ->pluck('change_count', 'shift_id');

        if ($changeCounts->isEmpty()) {
            return;
        }

        foreach ($cells as $craftId => $weekCells) {
            foreach ($weekCells as $weekKey => $aggregate) {
                foreach ($aggregate['shift_ids'] as $shiftId) {
                    $cells[$craftId][$weekKey]['open_changes'] += (int) ($changeCounts[$shiftId] ?? 0);
                }
            }
        }
    }

    /**
     * Aktive Regelverstöße (violation_date in der KW) von Personen, die in der KW eine
     * Schicht des Gewerks haben — je Verstoß gezählt, Zuordnung über die Zellen-User.
     */
    private function applyViolations(array &$cells, EloquentCollection $shifts, Carbon $from, Carbon $to): void
    {
        if ($shifts->isEmpty()) {
            return;
        }

        // [weekKey][userId] => Liste der Zellen (craftId), in denen der User eingeplant ist
        $cellsByWeekUser = [];
        foreach ($cells as $craftId => $weekCells) {
            foreach ($weekCells as $weekKey => $aggregate) {
                foreach (array_keys($aggregate['user_ids']) as $userId) {
                    $cellsByWeekUser[$weekKey][$userId][] = $craftId;
                }
            }
        }

        $userIds = [];
        foreach ($cellsByWeekUser as $users) {
            foreach (array_keys($users) as $userId) {
                $userIds[$userId] = true;
            }
        }

        if ($userIds === []) {
            return;
        }

        $violations = ShiftRuleViolation::query()
            ->select(['id', 'user_id', 'violation_date'])
            ->where('status', 'active')
            ->whereBetween('violation_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('user_id', array_keys($userIds))
            ->get();

        foreach ($violations as $violation) {
            $weekKey = $this->weekKey(Carbon::parse($violation->violation_date));
            $userId = (int) $violation->user_id;

            foreach ($cellsByWeekUser[$weekKey][$userId] ?? [] as $craftId) {
                $cells[$craftId][$weekKey]['violation_ids'][(int) $violation->id] = true;
            }
        }
    }

    /**
     * @param array<string, mixed> $aggregate
     * @param array<string, mixed> $week
     * @return array<string, mixed>
     */
    private function finalizeCell(array $aggregate, array $week, ?int $deadlineDays, Carbon $today): array
    {
        $status = $this->deriveStatus($aggregate);

        $deadlineDate = null;
        $deadlineState = null;
        if ($deadlineDays !== null) {
            $deadlineDate = Carbon::parse($week['monday'])->subDays($deadlineDays)->startOfDay();
            $deadlineState = $this->deriveDeadlineState($status, $deadlineDate, $today);
        }

        $request = $aggregate['pending_request'] ?? $aggregate['latest_request'];

        return [
            'week_key' => $week['key'],
            'week_number' => $week['week_number'],
            'year' => $week['year'],
            'status' => $status,
            'shifts_total' => $aggregate['shifts_total'],
            'shifts_committed' => $aggregate['shifts_committed'],
            'open_changes' => $aggregate['open_changes'],
            'open_violations' => count($aggregate['violation_ids']),
            'required_slots' => $aggregate['required_slots'],
            'staffed_slots' => $aggregate['staffed_slots'],
            'open_slots' => max(0, $aggregate['required_slots'] - $aggregate['staffed_slots']),
            'deadline_date' => $deadlineDate?->toDateString(),
            'deadline_date_formatted' => $deadlineDate?->format('d.m.Y'),
            'deadline_state' => $deadlineState,
            'request_id' => $request['id'] ?? null,
            'request_status' => $request['status'] ?? null,
            'requested_at' => $request['requested_at'] ?? null,
            'reviewed_at' => $request['reviewed_at'] ?? null,
            'review_comment' => $request['review_comment'] ?? null,
            'requested_by_user_id' => $request['requested_by_user_id'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $aggregate
     */
    private function deriveStatus(array $aggregate): string
    {
        if ($aggregate['shifts_total'] === 0) {
            return self::STATUS_NONE;
        }

        if ($aggregate['shifts_committed'] === $aggregate['shifts_total']) {
            return self::STATUS_COMMITTED;
        }

        if ($aggregate['pending_request'] !== null) {
            return self::STATUS_REQUESTED;
        }

        if (($aggregate['latest_request']['status'] ?? null) === 'rejected') {
            return self::STATUS_REJECTED;
        }

        if ($aggregate['shifts_committed'] > 0) {
            return self::STATUS_PARTIAL;
        }

        return self::STATUS_OPEN;
    }

    /**
     * ok | due_soon (Frist in ≤ DUE_SOON_DAYS Tagen) | overdue (Frist überschritten).
     * Ist die Woche bereits festgeschrieben/angefragt oder ohne Schichten, gilt die Frist als erfüllt.
     */
    private function deriveDeadlineState(string $status, Carbon $deadlineDate, Carbon $today): string
    {
        if (in_array($status, [self::STATUS_NONE, self::STATUS_COMMITTED, self::STATUS_REQUESTED], true)) {
            return 'ok';
        }

        if ($today->greaterThan($deadlineDate)) {
            return 'overdue';
        }

        if ($today->diffInDays($deadlineDate) <= self::DUE_SOON_DAYS) {
            return 'due_soon';
        }

        return 'ok';
    }
}
