<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoContext;
use Database\Seeders\Demo\Support\DemoDataPools;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Zuweisungs-Workflow: besetzt die Demo-Schichten passend zu Gewerk +
 * Funktion, respektiert Urlaube und Zeitüberschneidungen, befüllt das
 * shift_workers-Pivot (inkl. individueller Zeiten mit Vermerk und
 * Bestätigungsstatus), schreibt vergangene Schichten fest und provoziert
 * einige bewusste Demo-Konflikte. Universelle Gewerke (Azubis/VT) werden
 * gezielt gewerkfremd eingesetzt — mit Herkunfts-Kürzel im Pivot.
 */
class DemoShiftAssignmentSeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    private DemoContext $context;
    private DemoRandom $random;

    /** @var array<int, array<string, array<int, array{worker: object, universalAbbreviation: ?string}>>> craftId => qualName => Kandidaten */
    private array $pools = [];

    /** @var array<string, array<int, array{0: int, 1: int}>> "Type|id" => [[startTs, endTs], ...] */
    private array $busy = [];

    /** @var array<string, array<string, true>> "Type|id" => [date => true] */
    private array $vacationDays = [];

    private Carbon $windowStart;
    private Carbon $windowEnd;

    public function run(): void
    {
        $this->context = new DemoContext();
        $this->random = new DemoRandom('assignments');
        $this->windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $this->windowEnd = $this->windowStart->copy()->addMonths($this->months)->endOfDay();

        $this->repairPivotTimes();
        $this->buildPools();
        $this->loadBusyTimes();
        $this->loadVacations();

        $shifts = $this->demoShifts();
        $assigned = 0;
        foreach ($shifts as $shift) {
            $assigned += $this->staffShift($shift);
        }
        $this->command?->info(sprintf('Zuweisung: %d Schichtbesetzungen erzeugt.', $assigned));

        $this->commitPastShifts($shifts);
        $this->seedDemoConflicts($shifts);
    }

    /**
     * Repariert Alt-Zuweisungen ohne Zeiten/Kürzel: der echte Zuweisungsflow
     * (ShiftUserRepository etc.) setzt start/end + craft_abbreviation IMMER —
     * Rows mit NULL rendern im Worker-Panel als "null - null Raum".
     */
    private function repairPivotTimes(): void
    {
        $repaired = DB::update('
            UPDATE shift_workers sw
            JOIN shifts s ON s.id = sw.shift_id
            SET sw.start_date = COALESCE(sw.start_date, s.start_date),
                sw.end_date = COALESCE(sw.end_date, s.end_date),
                sw.start_time = COALESCE(sw.start_time, s.start),
                sw.end_time = COALESCE(sw.end_time, s.end)
            WHERE sw.start_time IS NULL OR sw.end_time IS NULL
               OR sw.start_date IS NULL OR sw.end_date IS NULL
        ');
        $repaired += DB::update('
            UPDATE shift_workers sw
            JOIN shifts s ON s.id = sw.shift_id
            JOIN crafts c ON c.id = s.craft_id
            SET sw.craft_abbreviation = c.abbreviation
            WHERE sw.craft_abbreviation IS NULL OR sw.craft_abbreviation = ""
        ');
        if ($repaired > 0) {
            $this->command?->info(sprintf('%d Zuweisungen ohne Zeiten/Kürzel repariert.', $repaired));
        }
    }

    /** @return Collection<int, Shift> */
    private function demoShifts(): Collection
    {
        $projectIds = Project::query()
            ->get(['id', 'name'])
            ->filter(static fn (Project $project) => DemoProjectPools::archetypeForProjectName($project->name) !== null)
            ->pluck('id');

        return Shift::query()
            ->whereIn('project_id', $projectIds)
            ->whereBetween('start_date', [$this->windowStart->toDateString(), $this->windowEnd->toDateString()])
            ->with(['shiftsQualifications'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->get();
    }

    private function buildPools(): void
    {
        $universalCraftIds = $this->context->universalCrafts()->pluck('id')->all();

        foreach ($this->context->crafts() as $craft) {
            $workers = collect()
                ->merge($craft->users()->get())
                ->merge($craft->freelancers()->get())
                ->merge($craft->serviceProviders()->get());

            foreach ($workers as $worker) {
                foreach ($worker->shiftQualifications as $qualification) {
                    $pivotCraftId = $qualification->pivot->craft_id;
                    if ($pivotCraftId !== null && (int) $pivotCraftId !== $craft->id) {
                        continue;
                    }
                    $this->pools[$craft->id][$qualification->name][] = [
                        'worker' => $worker,
                        'universalAbbreviation' => in_array($craft->id, $universalCraftIds, true)
                            ? $craft->abbreviation
                            : null,
                    ];
                }
            }
        }
    }

    private function loadBusyTimes(): void
    {
        $rows = ShiftWorker::query()
            ->whereHas('shift', fn ($query) => $query->whereBetween(
                'start_date',
                [$this->windowStart->toDateString(), $this->windowEnd->toDateString()]
            ))
            ->with('shift')
            ->get();

        foreach ($rows as $row) {
            if ($row->shift === null) {
                continue;
            }
            [$start, $end] = $this->shiftInterval($row->shift);
            $this->busy[$row->employable_type . '|' . $row->employable_id][] = [$start, $end];
        }
    }

    private function loadVacations(): void
    {
        $vacations = Vacation::query()
            ->whereBetween('date', [$this->windowStart->toDateString(), $this->windowEnd->toDateString()])
            ->get(['vacationer_type', 'vacationer_id', 'date']);

        foreach ($vacations as $vacation) {
            $key = $vacation->vacationer_type . '|' . $vacation->vacationer_id;
            $this->vacationDays[$key][Carbon::parse($vacation->date)->toDateString()] = true;
        }
    }

    /** @return array{0: int, 1: int} */
    private function shiftInterval(Shift $shift): array
    {
        $start = Carbon::parse($shift->start_date->format('Y-m-d') . ' ' . $shift->start);
        $end = Carbon::parse($shift->end_date->format('Y-m-d') . ' ' . $shift->end);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start->getTimestamp(), $end->getTimestamp()];
    }

    private function isAvailable(object $worker, Shift $shift, int $start, int $end): bool
    {
        $key = get_class($worker) . '|' . $worker->id;
        if (isset($this->vacationDays[$key][$shift->start_date->format('Y-m-d')])) {
            return false;
        }
        foreach ($this->busy[$key] ?? [] as [$busyStart, $busyEnd]) {
            if ($start < $busyEnd && $end > $busyStart) {
                return false;
            }
        }

        return true;
    }

    private function staffShift(Shift $shift): int
    {
        if (ShiftWorker::query()->where('shift_id', $shift->id)->exists()) {
            return 0;
        }

        $rng = $this->random->fork('shift|' . $shift->id);
        [$start, $end] = $this->shiftInterval($shift);
        $fill = $this->fillProbability($shift);
        $created = 0;

        foreach ($shift->shiftsQualifications as $demand) {
            $qualification = $this->context->qualifications()->firstWhere('id', $demand->shift_qualification_id);
            if ($qualification === null) {
                continue;
            }

            $regular = $this->pools[$shift->craft_id][$qualification->name] ?? [];
            $universal = [];
            foreach ($this->context->universalCrafts() as $universalCraft) {
                if ($universalCraft->id === $shift->craft_id) {
                    continue;
                }
                foreach ($this->pools[$universalCraft->id][$qualification->name] ?? [] as $candidate) {
                    $universal[] = $candidate;
                }
            }

            for ($slot = 0; $slot < (int) $demand->value; $slot++) {
                if (!$rng->chance($fill)) {
                    continue;
                }

                // Universelle Gewerke sichtbar machen: bei "Mitarbeiter"-Slots
                // regelmäßig Azubis/VT-Allrounder gewerkfremd einsetzen.
                $preferUniversal = $universal !== []
                    && $qualification->name === 'Mitarbeiter'
                    && $rng->chance(0.25);

                $candidate = $this->pickCandidate($rng, $preferUniversal ? $universal : $regular, $shift, $start, $end)
                    ?? $this->pickCandidate($rng, $preferUniversal ? $regular : $universal, $shift, $start, $end);
                if ($candidate === null) {
                    continue;
                }

                $this->assign($shift, $candidate, $qualification->id, $rng, $start, $end);
                $created++;
            }
        }

        return $created;
    }

    /** @param array<int, array{worker: object, universalAbbreviation: ?string}> $candidates */
    private function pickCandidate(DemoRandom $rng, array $candidates, Shift $shift, int $start, int $end): ?array
    {
        foreach ($rng->shuffle($candidates) as $candidate) {
            if ($this->isAvailable($candidate['worker'], $shift, $start, $end)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param array{worker: object, universalAbbreviation: ?string} $candidate */
    private function assign(
        Shift $shift,
        array $candidate,
        int $qualificationId,
        DemoRandom $rng,
        int $start,
        int $end,
        bool $markOverbooked = false
    ): ShiftWorker {
        $worker = $candidate['worker'];
        $shiftCraft = $this->context->crafts()->firstWhere('id', $shift->craft_id);

        // Wie der echte Zuweisungsflow (ShiftUserRepository): Zeiten + Kürzel IMMER setzen —
        // NULL-Werte rendern im Worker-Panel als "null - null Raum".
        $shiftStart = Carbon::parse($shift->start_date->format('Y-m-d') . ' ' . $shift->start);
        $shiftEnd = Carbon::parse($shift->end_date->format('Y-m-d') . ' ' . $shift->end);
        $attributes = [
            'shift_id' => $shift->id,
            'employable_type' => get_class($worker),
            'employable_id' => $worker->id,
            'shift_qualification_id' => $qualificationId,
            'shift_count' => 1,
            'craft_abbreviation' => $candidate['universalAbbreviation'] ?? $shiftCraft?->abbreviation,
            'assigned_by_user_id' => $this->context->plannerUser()->id,
            'is_overbooked' => $markOverbooked,
            'start_date' => $shift->start_date->format('Y-m-d'),
            'end_date' => $shift->end_date->format('Y-m-d'),
            'start_time' => $shiftStart->format('H:i'),
            'end_time' => $shiftEnd->format('H:i'),
        ];

        // ~15 % individuelle Zeiten mit passendem Vermerk im Pivot
        if ($rng->chance(0.15)) {
            $note = $rng->pick(DemoDataPools::SHIFT_WORKER_NOTES);
            $attributes['start_time'] = $shiftStart->copy()->addMinutes($note['start_offset'] ?? 0)->format('H:i');
            $attributes['end_time'] = $shiftEnd->copy()->addMinutes($note['end_offset'] ?? 0)->format('H:i');
            $attributes['short_description'] = $note['note'];
        }

        // Bestätigungsstatus: Vergangenheit fast immer bestätigt, nahe Zukunft gemischt
        $shiftDate = Carbon::parse($shift->start_date);
        if ($shiftDate->isPast()) {
            $attributes['confirmation_status'] = ShiftWorker::CONFIRMATION_ACCEPTED;
            $attributes['confirmation_at'] = $shiftDate->copy()->subDays($rng->int(2, 10));
        } elseif ($shiftDate->diffInDays(Carbon::now()) <= 30) {
            if ($rng->chance(0.6)) {
                $attributes['confirmation_status'] = ShiftWorker::CONFIRMATION_ACCEPTED;
                $attributes['confirmation_at'] = Carbon::now()->subDays($rng->int(0, 5));
            } elseif ($rng->chance(0.08)) {
                $attributes['confirmation_status'] = ShiftWorker::CONFIRMATION_DECLINED;
                $attributes['confirmation_at'] = Carbon::now()->subDays($rng->int(0, 5));
                $attributes['confirmation_comment'] = $rng->pick(DemoDataPools::DECLINE_COMMENTS);
            }
        }

        $shiftWorker = new ShiftWorker();
        $shiftWorker->forceFill($attributes)->save();

        $this->busy[get_class($worker) . '|' . $worker->id][] = [$start, $end];

        return $shiftWorker;
    }

    private function fillProbability(Shift $shift): float
    {
        $date = Carbon::parse($shift->start_date);
        if ($date->isPast()) {
            return 0.95;
        }

        return $date->diffInDays(Carbon::now()) <= 30 ? 0.85 : 0.5;
    }

    /** @param Collection<int, Shift> $shifts */
    private function commitPastShifts(Collection $shifts): void
    {
        $pastIds = $shifts
            ->filter(static fn (Shift $shift) => Carbon::parse($shift->start_date)->isPast())
            ->pluck('id');
        if ($pastIds->isEmpty()) {
            return;
        }

        $committed = Shift::query()
            ->whereIn('id', $pastIds)
            ->where('is_committed', false)
            ->update([
                'is_committed' => true,
                'committing_user_id' => $this->context->plannerUser()->id,
            ]);
        if ($committed > 0) {
            $this->command?->info(sprintf('%d vergangene Schichten festgeschrieben.', $committed));
        }
    }

    /**
     * Bewusste Demo-Konflikte: Doppelbuchung, Einsatz trotz Urlaub,
     * Überbuchung und zwei manuelle Regelverletzungen — damit die
     * Warn-Features in Demos sofort sichtbar sind.
     */
    /** @param Collection<int, Shift> $shifts */
    private function seedDemoConflicts(Collection $shifts): void
    {
        $futureShifts = $shifts->filter(
            static fn (Shift $shift) => Carbon::parse($shift->start_date)->isFuture()
        )->values();
        if ($futureShifts->isEmpty()) {
            return;
        }
        $rng = $this->random->fork('conflicts');

        $this->seedDoubleBooking($futureShifts, $rng);
        $this->seedVacationConflict($futureShifts, $rng);
        $this->seedOverbooking($futureShifts, $rng);
        $this->seedConsecutiveDaysStreak($futureShifts, $rng);
        $this->seedManualRuleViolations($shifts, $rng);
    }

    /**
     * Bewusster Regelverstoß "Höchstens 6 Arbeitstage in Folge": ein NV-Bühne-User
     * wird an 7+ aufeinanderfolgenden Tagen eingeplant — die echte Regel-Engine
     * erzeugt daraus die Verletzung(en), die in der Übersicht zeigbar sind.
     */
    private function seedConsecutiveDaysStreak(Collection $futureShifts, DemoRandom $rng): void
    {
        $streakUser = $this->context->demoUser('Ole', 'Jensen');
        if ($streakUser === null) {
            return;
        }
        $mitarbeiter = $this->context->qualification('mitarbeiter');

        // Schichten nach Tag gruppieren und die längste erreichbare Tagesfolge nehmen
        $byDay = $futureShifts->groupBy(static fn (Shift $shift) => $shift->start_date->format('Y-m-d'))->sortKeys();
        $assignedDays = 0;
        $previousDay = null;
        foreach ($byDay as $day => $dayShifts) {
            if ($previousDay !== null && Carbon::parse($day)->diffInDays(Carbon::parse($previousDay)) > 1) {
                if ($assignedDays >= 7) {
                    break;
                }
                $assignedDays = 0; // Lücke — Streak neu beginnen
            }
            $previousDay = $day;

            $shift = $dayShifts->first();
            $alreadyAssigned = ShiftWorker::query()
                ->where('shift_id', $shift->id)
                ->where('employable_type', User::class)
                ->where('employable_id', $streakUser->id)
                ->exists();
            if (!$alreadyAssigned) {
                [$start, $end] = $this->shiftInterval($shift);
                $this->assign(
                    $shift,
                    ['worker' => $streakUser, 'universalAbbreviation' => null],
                    $mitarbeiter->id,
                    $rng->fork('streak|' . $shift->id),
                    $start,
                    $end
                );
            }
            $assignedDays++;
            if ($assignedDays >= 8) {
                break;
            }
        }

        if ($assignedDays >= 7) {
            $this->command?->info(sprintf(
                'Demo-Regelverstoß: %s an %d Tagen in Folge eingeplant (Engine erzeugt Verletzungen).',
                $streakUser->first_name . ' ' . $streakUser->last_name,
                $assignedDays
            ));
        }
    }

    /** Ein Azubi wird auf zwei parallele Schichten desselben Termins gebucht (Mehrfacheinsatz-Warnung). */
    private function seedDoubleBooking(Collection $futureShifts, DemoRandom $rng): void
    {
        $byEvent = $futureShifts->whereNotNull('event_id')->groupBy('event_id')
            ->first(static fn (Collection $group) => $group->count() >= 2);
        if ($byEvent === null) {
            return;
        }
        [$first, $second] = [$byEvent->values()->get(0), $byEvent->values()->get(1)];

        $azubiCraft = $this->context->craft('azubi');
        $candidates = $this->pools[$azubiCraft?->id]['Mitarbeiter'] ?? [];
        if ($candidates === []) {
            return;
        }
        $candidate = $candidates[0];
        $worker = $candidate['worker'];

        $alreadyOnBoth = ShiftWorker::query()
            ->whereIn('shift_id', [$first->id, $second->id])
            ->where('employable_type', get_class($worker))
            ->where('employable_id', $worker->id)
            ->count() >= 2;
        if ($alreadyOnBoth) {
            return;
        }

        $mitarbeiterId = $this->context->qualification('mitarbeiter')->id;
        foreach ([$first, $second] as $shift) {
            $exists = ShiftWorker::query()
                ->where('shift_id', $shift->id)
                ->where('employable_type', get_class($worker))
                ->where('employable_id', $worker->id)
                ->exists();
            if (!$exists) {
                [$start, $end] = $this->shiftInterval($shift);
                $this->assign($shift, $candidate, $mitarbeiterId, $rng->fork('double|' . $shift->id), $start, $end);
            }
        }
        $this->command?->info('Demo-Konflikt: Doppelbuchung angelegt (' . $worker->first_name . ' ' . $worker->last_name . ').');
    }

    /** Ein bereits eingeplanter User bekommt Urlaub am Schichttag (Warndreieck "nicht verfügbar"). */
    private function seedVacationConflict(Collection $futureShifts, DemoRandom $rng): void
    {
        $marker = 'Demo: Urlaub trotz Einplanung';
        if (Vacation::query()->where('comment', $marker)->exists()) {
            return;
        }

        $assignment = ShiftWorker::query()
            ->whereIn('shift_id', $futureShifts->pluck('id'))
            ->where('employable_type', User::class)
            ->with('shift')
            ->orderBy('id')
            ->first();
        if ($assignment === null || $assignment->shift === null) {
            return;
        }

        Vacation::create([
            'vacationer_type' => User::class,
            'vacationer_id' => $assignment->employable_id,
            'date' => Carbon::parse($assignment->shift->start_date)->toDateString(),
            'full_day' => true,
            'day_part' => 'full',
            'type' => 'OFF_WORK',
            'comment' => $marker,
            'is_series' => false,
            'created_by' => $this->context->plannerUser()->id,
        ]);
        $this->command?->info('Demo-Konflikt: Einplanung trotz Urlaub angelegt.');
    }

    /** Eine Zukunfts-Schicht wird über den Bedarf hinaus besetzt (is_overbooked). */
    private function seedOverbooking(Collection $futureShifts, DemoRandom $rng): void
    {
        foreach ($futureShifts as $shift) {
            if (ShiftWorker::query()->where('shift_id', $shift->id)->where('is_overbooked', true)->exists()) {
                return; // bereits vorhanden — idempotent
            }
        }

        foreach ($futureShifts as $shift) {
            $demand = $shift->shiftsQualifications->first();
            if ($demand === null) {
                continue;
            }
            $assignedCount = ShiftWorker::query()
                ->where('shift_id', $shift->id)
                ->where('shift_qualification_id', $demand->shift_qualification_id)
                ->count();
            if ($assignedCount < (int) $demand->value) {
                continue;
            }

            $qualification = $this->context->qualifications()->firstWhere('id', $demand->shift_qualification_id);
            [$start, $end] = $this->shiftInterval($shift);
            $candidate = $this->pickCandidate(
                $rng,
                $this->pools[$shift->craft_id][$qualification?->name] ?? [],
                $shift,
                $start,
                $end
            );
            if ($candidate === null) {
                continue;
            }

            $this->assign($shift, $candidate, $demand->shift_qualification_id, $rng->fork('overbook'), $start, $end, true);
            $demand->update(['overbooked_value' => $assignedCount + 1 - (int) $demand->value]);
            $this->command?->info('Demo-Konflikt: Überbuchung angelegt.');

            return;
        }
    }

    /** Zwei manuelle Regelverletzungen, damit die Regelverletzungs-Übersicht Daten zeigt. */
    private function seedManualRuleViolations(Collection $shifts, DemoRandom $rng): void
    {
        $rule = ShiftRule::query()->where('name', 'Max. 10 Stunden pro Tag')->first();
        if ($rule === null || $rule->shiftRuleViolations()->exists()) {
            return;
        }

        $assignments = ShiftWorker::query()
            ->whereIn('shift_id', $shifts->pluck('id'))
            ->where('employable_type', User::class)
            ->with('shift')
            ->orderBy('id')
            ->limit(2)
            ->get();

        foreach ($assignments as $assignment) {
            if ($assignment->shift === null) {
                continue;
            }
            ShiftRuleViolation::create([
                'shift_rule_id' => $rule->id,
                'shift_id' => $assignment->shift_id,
                'user_id' => $assignment->employable_id,
                'violation_date' => Carbon::parse($assignment->shift->start_date)->toDateString(),
                'violation_data' => ['seeded' => true, 'hours' => 10.5],
                'severity' => 'warning',
                'status' => 'active',
                'reason' => 'Geplante Dienste überschreiten 10 Stunden an einem Tag (Demo-Daten).',
                'is_manual' => true,
                'created_by_user_id' => $this->context->plannerUser()->id,
            ]);
        }
        $this->command?->info('Demo: 2 Regelverletzungen zur Ansicht angelegt.');
    }
}
