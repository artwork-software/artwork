<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

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
 */
class ShiftPlanWorkflowStatusService
{
    private const TYPE_KEYS = [
        User::class => 'user',
        Freelancer::class => 'freelancer',
        ServiceProvider::class => 'service_provider',
    ];

    /**
     * @param class-string|null $onlyEmployableType Zusammen mit $onlyEmployableId: nur Schichten
     *                                              dieser einen Person laden (Einzel-Reload)
     * @return array<string, array<int, array<string, string>>>
     *         [typeKey][workerId][weekNumber] => status
     */
    public function computeForDateRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $onlyEmployableType = null,
        ?int $onlyEmployableId = null
    ): array {
        $rangeStart = $startDate->toDateString();
        $rangeEnd = $endDate->toDateString();

        $vacationsScope = fn ($query) => $query
            ->without(['series', 'conflicts'])
            ->whereBetween('date', [$rangeStart, $rangeEnd]);

        $shifts = Shift::query()
            ->select(['id', 'start_date', 'end_date', 'start', 'end', 'is_committed', 'in_workflow'])
            ->whereBetween('start_date', [$rangeStart, $rangeEnd])
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

        // [typeKey][workerId][week] => ['total' => int, 'committed' => int, 'requested' => bool, 'attention' => bool]
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

                $week = $this->weekNumber($worker->pivot?->start_date ?? $shift->start_date);
                $entry = &$aggregate[$typeKey][$worker->id][$week];
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

        $this->applyUnacknowledgedChanges($aggregate, $shifts, $rangeStart, $rangeEnd);

        $statusMap = [];
        foreach ($aggregate as $typeKey => $workerWeeks) {
            foreach ($workerWeeks as $workerId => $weeks) {
                foreach ($weeks as $week => $entry) {
                    $status = match (true) {
                        $entry['attention'] => 'attention',
                        $entry['requested'] => 'requested',
                        $entry['total'] > 0 && $entry['committed'] === $entry['total'] => 'committed',
                        default => null,
                    };

                    if ($status !== null) {
                        $statusMap[$typeKey][$workerId][$week] = $status;
                    }
                }
            }
        }

        return $statusMap;
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
        string $rangeEnd
    ): void {
        $changes = CommittedShiftChange::query()
            ->join('shifts', 'shifts.id', '=', 'committed_shift_changes.shift_id')
            ->whereNull('committed_shift_changes.acknowledged_at')
            ->whereBetween('shifts.start_date', [$rangeStart, $rangeEnd])
            ->get([
                'committed_shift_changes.shift_id',
                'committed_shift_changes.affected_user_type',
                'committed_shift_changes.affected_user_id',
                'shifts.start_date as shift_start_date',
            ]);

        if ($changes->isEmpty()) {
            return;
        }

        $shiftsById = $shifts->keyBy('id');

        foreach ($changes as $change) {
            $week = $this->weekNumber($change->shift_start_date);

            if ($change->affected_user_id !== null) {
                $typeKey = self::TYPE_KEYS[$change->affected_user_type] ?? null;
                if ($typeKey !== null) {
                    $entry = &$aggregate[$typeKey][$change->affected_user_id][$week];
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

                $entry = &$aggregate[$typeKey][$worker->id][$week];
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
        return ltrim(Carbon::parse($date)->format('W'), '0');
    }
}
