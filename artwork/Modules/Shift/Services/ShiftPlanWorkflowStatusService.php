<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Berechnet pro Person und Kalenderwoche den Freigabe-Zustand für die
 * KW-Kacheln im Schichtplan:
 *
 *  - "requested":  mindestens eine Schicht der Person in der KW ist zur
 *                  Freigabe angefragt (in_workflow, noch nicht festgeschrieben)
 *  - "committed":  alle Schichten der Person in der KW sind festgeschrieben
 *  - "attention":  eine festgeschriebene Schicht wurde nachträglich geändert
 *                  (unbestätigte CommittedShiftChange) oder die Person ist auf
 *                  einer festgeschriebenen Schicht eingeplant, aber nicht
 *                  verfügbar (z.B. nachträglich krank gemeldet)
 *
 * Priorität: attention > requested > committed. Wochen ohne eindeutigen
 * Zustand fehlen in der Map (neutral).
 *
 * Individuelle Zeiten laufen wie Schichten durch die Freigabe, gehören aber zur
 * Person statt zu einem Gewerk. Sie zählen deshalb in jeder Gewerk-Zeile der
 * Person mit; Wochen, in denen die Person in einem Gewerk keine Schicht hat,
 * stehen unter dem Schlüssel INDIVIDUAL_TIMES_KEY (nur individuelle Zeiten).
 * Hat eine Gewerk-Woche dadurch keinen eindeutigen Zustand, obwohl die Person
 * individuelle Zeiten hat, steht dort 'open', damit das Frontend nicht auf den
 * Wert unter INDIVIDUAL_TIMES_KEY zurückfällt.
 */
class ShiftPlanWorkflowStatusService
{
    private const TYPE_KEYS = [
        User::class => 'user',
        Freelancer::class => 'freelancer',
        ServiceProvider::class => 'service_provider',
    ];

    /** Gewerk-Schlüssel für Wochen, in denen die Person nur individuelle Zeiten hat */
    public const INDIVIDUAL_TIMES_KEY = '*';

    /** @var array<string, string> */
    private array $weekNumberCache = [];

    /**
     * @param class-string|null $onlyEmployableType Zusammen mit $onlyEmployableId: nur Schichten
     *                                              dieser einen Person laden (Einzel-Reload)
     * @param array<int, int> $craftIds
     * @return array<string, array<int, array<int, array<string, string>>>>
     *         [typeKey][workerId][craftId][weekNumber] => status
     */
    public function computeForDateRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $onlyEmployableType = null,
        ?int $onlyEmployableId = null,
        array $craftIds = []
    ): array {
        $rangeStart = $startDate->toDateString();
        $rangeEnd = $endDate->toDateString();

        $vacationsScope = fn ($query) => $query
            ->without(['series', 'conflicts'])
            ->whereBetween('date', [$rangeStart, $rangeEnd]);

        $shifts = Shift::query()
            ->select(['id', 'craft_id', 'start_date', 'end_date', 'start', 'end', 'is_committed', 'in_workflow'])
            ->whereBetween('start_date', [$rangeStart, $rangeEnd])
            ->when($craftIds !== [], fn ($query) => $query->whereIn('craft_id', $craftIds))
            ->when(
                $onlyEmployableType !== null && $onlyEmployableId !== null,
                fn ($query) => $query->whereExists(
                    fn ($sub) => $sub
                        ->from('shift_workers')
                        ->whereColumn('shift_workers.shift_id', 'shifts.id')
                        ->whereNull('shift_workers.deleted_at')
                        ->where('shift_workers.employable_type', $onlyEmployableType)
                        ->where('shift_workers.employable_id', $onlyEmployableId)
                )
            )
            ->with([
                'users:id',
                'users.vacations' => $vacationsScope,
                'freelancer:id',
                'freelancer.vacations' => $vacationsScope,
                'serviceProvider:id',
                'serviceProvider.vacations' => $vacationsScope,
            ])
            ->get();

        // [typeKey][workerId][craftId][week] => status counters
        $aggregate = [];

        foreach ($shifts as $shift) {
            $workers = $shift->users
                ->concat($shift->freelancer)
                ->concat($shift->serviceProvider);

            foreach ($workers as $worker) {
                $typeKey = self::TYPE_KEYS[get_class($worker)] ?? null;
                if ($typeKey === null) {
                    continue;
                }

                $craftId = (int) $shift->craft_id;
                $week = $this->weekNumber($worker->pivot?->start_date ?? $shift->start_date);
                $entry = &$aggregate[$typeKey][$worker->id][$craftId][$week];
                $entry ??= ['total' => 0, 'committed' => 0, 'requested' => false, 'attention' => false];

                $entry['total']++;

                if ($shift->is_committed) {
                    $entry['committed']++;

                    if (ShiftWorkerAvailability::isWorkerUnavailable($shift, $worker)) {
                        $entry['attention'] = true;
                    }
                } elseif ($shift->in_workflow) {
                    $entry['requested'] = true;
                }

                unset($entry);
            }
        }

        $this->applyUnacknowledgedChanges($aggregate, $shifts, $rangeStart, $rangeEnd, $craftIds);

        $individualTimeWeeks = $this->aggregateIndividualTimes(
            $rangeStart,
            $rangeEnd,
            $onlyEmployableType,
            $onlyEmployableId
        );
        $this->mergeIndividualTimes($aggregate, $individualTimeWeeks);
        $this->applyUnacknowledgedIndividualTimeChanges(
            $aggregate,
            $rangeStart,
            $rangeEnd,
            $onlyEmployableType,
            $onlyEmployableId
        );

        $statusMap = [];
        foreach ($aggregate as $typeKey => $workerWeeks) {
            foreach ($workerWeeks as $workerId => $craftWeeks) {
                foreach ($craftWeeks as $craftId => $weeks) {
                    foreach ($weeks as $week => $entry) {
                        $status = match (true) {
                            $entry['attention'] => 'attention',
                            $entry['requested'] => 'requested',
                            $entry['total'] > 0 && $entry['committed'] === $entry['total'] => 'committed',
                            default => null,
                        };

                        if ($status !== null) {
                            $statusMap[$typeKey][$workerId][$craftId][$week] = $status;
                        } elseif (
                            $craftId !== self::INDIVIDUAL_TIMES_KEY
                            && isset($individualTimeWeeks[$typeKey][$workerId][$week])
                        ) {
                            $statusMap[$typeKey][$workerId][$craftId][$week] = 'open';
                        }
                    }
                }
            }
        }

        return $statusMap;
    }

    /**
     * Zähler je Person und KW für individuelle Zeiten im Zeitraum (mehrtägige Zeiten zählen in jeder
     * berührten KW).
     *
     * @return array<string, array<int, array<string, array{total: int, committed: int, requested: bool}>>>
     */
    private function aggregateIndividualTimes(
        string $rangeStart,
        string $rangeEnd,
        ?string $onlyEmployableType,
        ?int $onlyEmployableId
    ): array {
        $individualTimes = IndividualTime::query()
            ->individualByDateRange($rangeStart, $rangeEnd)
            ->whereIn('timeable_type', array_keys(self::TYPE_KEYS))
            // Nur Zeiten mit Freigabe-Bezug (angefragt oder freigegeben). Zeiten außerhalb jeder Anfrage
            // (Bestand vor dem Workflow, Häuser ohne Freigabe-Workflow, direktes Festschreiben) dürfen eine
            // festgeschriebene Woche nicht wieder auf „offen“ setzen.
            ->where(fn ($query) => $query->where('is_committed', true)->orWhere('in_workflow', true))
            ->when(
                $onlyEmployableType !== null && $onlyEmployableId !== null,
                fn ($query) => $query
                    ->where('timeable_type', $onlyEmployableType)
                    ->where('timeable_id', $onlyEmployableId)
            )
            ->get(['id', 'timeable_type', 'timeable_id', 'start_date', 'end_date', 'in_workflow', 'is_committed']);

        $weeks = [];
        foreach ($individualTimes as $individualTime) {
            $typeKey = self::TYPE_KEYS[$individualTime->timeable_type];
            $from = max($individualTime->start_date, $rangeStart);
            $to = min($individualTime->end_date, $rangeEnd);

            $touchedWeeks = [];
            foreach (CarbonPeriod::create($from, $to) as $day) {
                $touchedWeeks[$this->weekNumber($day->toDateString())] = true;
            }

            foreach (array_keys($touchedWeeks) as $week) {
                $entry = &$weeks[$typeKey][(int) $individualTime->timeable_id][(string) $week];
                $entry ??= ['total' => 0, 'committed' => 0, 'requested' => false];
                $entry['total']++;
                if ($individualTime->is_committed) {
                    $entry['committed']++;
                } elseif ($individualTime->in_workflow) {
                    $entry['requested'] = true;
                }
                unset($entry);
            }
        }

        return $weeks;
    }

    /**
     * Zählt die individuellen Zeiten in jede vorhandene Gewerk-Woche der Person ein und legt sie
     * zusätzlich unter INDIVIDUAL_TIMES_KEY ab (Gewerk-Zeilen ohne Schicht in der KW).
     *
     * @param array<string, array<int, array<string, array<string, int|bool>>>> $individualTimeWeeks
     */
    private function mergeIndividualTimes(array &$aggregate, array $individualTimeWeeks): void
    {
        foreach ($individualTimeWeeks as $typeKey => $workers) {
            foreach ($workers as $workerId => $weeks) {
                foreach ($weeks as $week => $counts) {
                    foreach ($aggregate[$typeKey][$workerId] ?? [] as $craftId => $craftWeeks) {
                        if (!isset($craftWeeks[$week])) {
                            continue;
                        }
                        $entry = &$aggregate[$typeKey][$workerId][$craftId][$week];
                        $entry['total'] += $counts['total'];
                        $entry['committed'] += $counts['committed'];
                        $entry['requested'] = $entry['requested'] || $counts['requested'];
                        unset($entry);
                    }

                    $aggregate[$typeKey][$workerId][self::INDIVIDUAL_TIMES_KEY][$week] = $counts
                        + ['attention' => false];
                }
            }
        }
    }

    /**
     * Unbestätigte Änderungen an freigegebenen individuellen Zeiten (auch gelöschten) machen die KW
     * der Person gelb — in jeder Gewerk-Zeile und unter INDIVIDUAL_TIMES_KEY, wie bei Schichten.
     */
    private function applyUnacknowledgedIndividualTimeChanges(
        array &$aggregate,
        string $rangeStart,
        string $rangeEnd,
        ?string $onlyEmployableType,
        ?int $onlyEmployableId
    ): void {
        $startPath = "JSON_UNQUOTE(JSON_EXTRACT(field_changes, '$._individual_time.start_date'))";
        $endPath = "JSON_UNQUOTE(JSON_EXTRACT(field_changes, '$._individual_time.end_date'))";

        $changes = CommittedShiftChange::query()
            ->where('subject_type', IndividualTime::class)
            ->whereNull('acknowledged_at')
            ->whereIn('affected_user_type', array_keys(self::TYPE_KEYS))
            ->whereRaw("$startPath <= ?", [$rangeEnd])
            ->whereRaw("COALESCE($endPath, $startPath) >= ?", [$rangeStart])
            ->when(
                $onlyEmployableType !== null && $onlyEmployableId !== null,
                fn ($query) => $query
                    ->where('affected_user_type', $onlyEmployableType)
                    ->where('affected_user_id', $onlyEmployableId)
            )
            ->get(['affected_user_type', 'affected_user_id', 'field_changes']);

        foreach ($changes as $change) {
            $typeKey = self::TYPE_KEYS[$change->affected_user_type];
            $workerId = (int) $change->affected_user_id;
            $state = $change->field_changes['_individual_time'] ?? [];
            $from = max((string) $state['start_date'], $rangeStart);
            $to = min((string) ($state['end_date'] ?? $state['start_date']), $rangeEnd);

            foreach (CarbonPeriod::create($from, $to) as $day) {
                $week = $this->weekNumber($day->toDateString());

                foreach ($aggregate[$typeKey][$workerId] ?? [] as $craftId => $craftWeeks) {
                    if (isset($craftWeeks[$week])) {
                        $aggregate[$typeKey][$workerId][$craftId][$week]['attention'] = true;
                    }
                }

                $entry = &$aggregate[$typeKey][$workerId][self::INDIVIDUAL_TIMES_KEY][$week];
                $entry ??= ['total' => 0, 'committed' => 0, 'requested' => false, 'attention' => false];
                $entry['attention'] = true;
                unset($entry);
            }
        }
    }

    /**
     * Markiert Wochen mit unbestätigten Änderungen an festgeschriebenen
     * Schichten als "attention". Gezielt betroffene Personen (affected_user)
     * werden auch markiert, wenn sie inzwischen nicht mehr eingeplant sind
     * (z.B. nach Festschreibung entfernt); Änderungen ohne konkrete Person
     * (etwa Zeitänderungen) betreffen alle aktuell eingeplanten Personen.
     */
    private function applyUnacknowledgedChanges(
        array &$aggregate,
        $shifts,
        string $rangeStart,
        string $rangeEnd,
        array $craftIds
    ): void {
        $changes = CommittedShiftChange::query()
            ->join('shifts', 'shifts.id', '=', 'committed_shift_changes.shift_id')
            // Änderungen an Schichten im Papierkorb verlangen keine Aufmerksamkeit mehr
            ->whereNull('shifts.deleted_at')
            ->whereNull('committed_shift_changes.acknowledged_at')
            ->whereBetween('shifts.start_date', [$rangeStart, $rangeEnd])
            ->when($craftIds !== [], fn ($query) => $query->whereIn('shifts.craft_id', $craftIds))
            ->get([
                'committed_shift_changes.shift_id',
                'committed_shift_changes.craft_id',
                'committed_shift_changes.affected_user_type',
                'committed_shift_changes.affected_user_id',
                'shifts.craft_id as shift_craft_id',
                'shifts.start_date as shift_start_date',
            ]);

        if ($changes->isEmpty()) {
            return;
        }

        $shiftsById = $shifts->keyBy('id');

        foreach ($changes as $change) {
            $craftId = (int) ($change->craft_id ?? $change->shift_craft_id);
            $week = $this->weekNumber($change->shift_start_date);

            if ($change->affected_user_id !== null) {
                $typeKey = self::TYPE_KEYS[$change->affected_user_type] ?? null;
                if ($typeKey !== null) {
                    $entry = &$aggregate[$typeKey][$change->affected_user_id][$craftId][$week];
                    $entry ??= ['total' => 0, 'committed' => 0, 'requested' => false, 'attention' => false];
                    $entry['attention'] = true;
                    unset($entry);
                }
                continue;
            }

            // Änderung ohne konkrete Person: alle aktuell eingeplanten Personen der Schicht
            $shift = $shiftsById->get($change->shift_id);
            if ($shift === null) {
                continue;
            }

            $workers = $shift->users
                ->concat($shift->freelancer)
                ->concat($shift->serviceProvider);

            foreach ($workers as $worker) {
                $typeKey = self::TYPE_KEYS[get_class($worker)] ?? null;
                if ($typeKey === null) {
                    continue;
                }

                $entry = &$aggregate[$typeKey][$worker->id][$craftId][$week];
                $entry ??= ['total' => 0, 'committed' => 0, 'requested' => false, 'attention' => false];
                $entry['attention'] = true;
                unset($entry);
            }
        }
    }

    /**
     * KW-Schlüssel ohne führende Null — identisch zum weeklyWorkingHours-Format
     * (WorkingHourService), damit das Frontend beide Maps gleich adressieren kann.
     */
    private function weekNumber(mixed $date): string
    {
        // Je Schicht × Person aufgerufen (Monatsansicht: mehrere tausend Mal) — Ergebnis je Datum merken
        $key = $date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : (string) $date;

        return $this->weekNumberCache[$key] ??= ltrim(Carbon::parse($date)->format('W'), '0');
    }
}
