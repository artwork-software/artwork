<?php

namespace Artwork\Modules\Shift\Console\Commands;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class InspectShiftRulesCommand extends Command
{
    protected $signature = 'shift-rules:inspect {--user_id=} {--name=} {--start=} {--end=}';

    protected $description = 'Inspect shifts and active shift rules for a user in a given date range';

    public function handle(): int
    {
        $user = $this->resolveUser();
        if (!$user) {
            $this->error('User not found. Provide --user_id or --name.');
            return self::FAILURE;
        }

        $start = $this->option('start') ? Carbon::parse($this->option('start')) : now()->startOfWeek();
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : now()->endOfWeek();

        $this->info("Inspecting user: {$user->first_name} {$user->last_name} (ID {$user->id})");
        $this->info("Date range: {$start->toDateString()} - {$end->toDateString()}");

        $contract = $user->activeWorkContract();
        if (!$contract) {
            $this->warn('No active contract found for user.');
            return self::SUCCESS;
        }

        // Active rules for contract
        /** @var Collection<ShiftRule> $rules */
        $rules = $contract->shiftRules()->where('is_active', true)->get();
        $this->line("Active rules for contract #{$contract->id}:");
        $this->table(['ID', 'Name', 'Trigger', 'Value', 'Color'],
            $rules->map(fn($r) => [$r->id, $r->name, $r->trigger_type, $r->individual_number_value, $r->warning_color])->toArray()
        );

        // Shifts in range
        $shifts = Shift::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->orderBy('start_date')
            ->orderBy('start')
            ->get();

        $this->line('Shifts in range:');
        $this->table([
            'ID', 'Start date', 'Start', 'End date', 'End', 'Break (min)', 'Committed'
        ], $shifts->map(function ($s) {
            return [
                $s->id,
                $s->start_date,
                $s->start,
                $s->end_date,
                $s->end,
                $s->break_minutes,
                $s->is_committed ? 'yes' : 'no',
            ];
        })->toArray());

        // Individual times in range
        $its = $user->individualTimes()
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->orderBy('start_date')
            ->orderByRaw('COALESCE(start_time, "00:00:00")')
            ->get();
        $this->line('Individual times in range:');
        $this->table([
            'ID', 'Title', 'Start date', 'Start', 'End date', 'End', 'Working (min)'
        ], $its->map(function ($it) {
            return [
                $it->id,
                $it->title,
                $it->start_date,
                $it->start_time,
                $it->end_date,
                $it->end_time,
                $it->working_time_minutes,
            ];
        })->toArray());

        // Daily planned hours
        $period = CarbonPeriod::create($start, $end);
        $daily = collect();
        foreach ($period as $date) {
            $hours = $this->getPlannedWorkingHoursForDay($user, $date);
            $daily->push([
                'date' => $date->toDateString(),
                'weekday' => $date->format('D'),
                'hours' => round($hours, 2),
            ]);
        }
        $this->line('Planned hours per day:');
        $this->table(['Date', 'Weekday', 'Hours'], $daily->toArray());

        // Weekly totals (ISO week)
        $weeklyTotals = $daily->groupBy(function ($row) {
            return Carbon::parse($row['date'])->isoWeekYear() . '-W' . str_pad((string)Carbon::parse($row['date'])->isoWeek(), 2, '0', STR_PAD_LEFT);
        })->map(fn($g) => round(collect($g)->sum('hours'), 2));

        $this->line('Weekly totals (ISO week):');
        $this->table(['ISO Week', 'Hours'], $weeklyTotals->map(fn($h, $w) => [$w, $h])->values()->toArray());

        // Rest times between previous day and current day
        $this->line('Rest time (hours) from previous day last shift to current day first shift:');
        $restRows = [];
        $latestShiftEndLastDay = null;
        foreach (CarbonPeriod::create($start->copy()->subDay(), $end) as $date) {
            if ($date->lt($start)) {
                $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($user->id, $date);
                continue;
            }
            $earliestStart = $this->getEarliestShiftStartOfDay($user->id, $date);
            if ($latestShiftEndLastDay && $earliestStart) {
                $restHours = $this->calculateRestHours($latestShiftEndLastDay, $earliestStart);
                $restRows[] = [
                    $date->toDateString(),
                    $latestShiftEndLastDay->format('Y-m-d H:i'),
                    $earliestStart->format('Y-m-d H:i'),
                    round($restHours, 2),
                ];
            }
            $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($user->id, $date);
        }
        if (empty($restRows)) {
            $this->line('No consecutive workdays with shifts found for rest time calculation.');
        } else {
            $this->table(['Date', 'Prev day last end', 'Current day first start', 'Rest (h)'], $restRows);
        }

        $this->info('Inspection completed. Compare above numbers with your rule thresholds to understand why violations did or did not trigger.');
        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $userId = $this->option('user_id');
        $name = $this->option('name');
        if ($userId) {
            return User::find($userId);
        }
        if ($name) {
            return User::whereRaw("concat(first_name, ' ', last_name) like ?", ["%{$name}%"])->first();
        }
        return null;
    }

    private function getPlannedWorkingHoursForDay($user, Carbon $date): float
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        // Shifts overlapping this date
        $shifts = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', is_int($user) ? $user : $user->id);
        })
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        $totalMinutes = 0;
        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start);
            $end = Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
            $breakMinutes = $shift->break_minutes ?? 0;
            $segStart = $start->greaterThan($dayStart) ? $start : $dayStart;
            $segEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;
            $totalMinutes += max(0, $segStart->diffInMinutes($segEnd) - $breakMinutes);
        }

        // Individual times overlapping this date
        $its = (is_int($user) ? User::find($user) : $user)
            ->individualTimes()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        foreach ($its as $it) {
            $itStart = Carbon::parse($it->start_date);
            $itEnd = Carbon::parse($it->end_date);
            $itStart->setTimeFromTimeString($it->start_time ?: '00:00:00');
            $itEnd->setTimeFromTimeString($it->end_time ?: '23:59:59');
            $segStart = $itStart->greaterThan($dayStart) ? $itStart : $dayStart;
            $segEnd = $itEnd->lessThan($dayEnd) ? $itEnd : $dayEnd;
            $totalMinutes += max(0, $segStart->diffInMinutes($segEnd));
        }

        return $totalMinutes / 60.0;
    }

    private function getEarliestShiftStartOfDay($user, Carbon $date): ?Carbon
    {
        $uid = is_int($user) ? $user : $user->id;
        $shift = Shift::whereHas('users', function ($query) use ($uid): void {
            $query->where('user_id', $uid);
        })
            ->whereDate('start_date', $date)
            ->orderBy('start')
            ->first();
        $earliest = $shift ? Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start) : null;

        $userObj = is_int($user) ? User::find($user) : $user;
        $it = $userObj->individualTimes()
            ->whereDate('start_date', $date)
            ->orderByRaw('COALESCE(start_time, "00:00:00")')
            ->first();
        if ($it) {
            $itStart = Carbon::parse($it->start_date);
            $itStart->setTimeFromTimeString($it->start_time ?: '00:00:00');
            $earliest = $earliest ? $earliest->min($itStart) : $itStart;
        }

        return $earliest;
    }

    private function getLatestShiftEndOfDay($user, Carbon $date): ?Carbon
    {
        $uid = is_int($user) ? $user : $user->id;
        $shift = Shift::whereHas('users', function ($query) use ($uid): void {
            $query->where('user_id', $uid);
        })
            ->where(function ($query) use ($date): void {
                $query->whereDate('end_date', $date)
                    ->orWhere(function ($q) use ($date): void {
                        $q->whereDate('start_date', $date)
                            ->whereRaw('end_date > start_date');
                    });
            })
            ->orderByDesc('end_date')
            ->orderByDesc('end')
            ->first();

        $latest = null;
        if ($shift) {
            if ($shift->start_date === $shift->end_date) {
                $latest = Carbon::parse($date->format('Y-m-d'))->setTimeFromTimeString($shift->end);
            } else {
                $latest = Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
            }
        }

        $userObj = is_int($user) ? User::find($user) : $user;
        $it = $userObj->individualTimes()
            ->where(function ($q) use ($date): void {
                $q->whereDate('end_date', $date)
                    ->orWhere(function ($qq) use ($date): void {
                        $qq->whereDate('start_date', $date)
                            ->whereRaw('end_date > start_date');
                    });
            })
            ->orderByDesc('end_date')
            ->orderByRaw('COALESCE(end_time, "23:59:59") DESC')
            ->first();

        if ($it) {
            $itEndDate = Carbon::parse($it->end_date);
            $itEndDate->setTimeFromTimeString($it->end_time ?: '23:59:59');
            $latest = $latest ? $latest->max($itEndDate) : $itEndDate;
        }

        return $latest;
    }

    private function calculateRestHours(Carbon $lastEnd, Carbon $nextStart): float
    {
        if ($nextStart <= $lastEnd) {
            return 0.0;
        }
        return $lastEnd->diffInMinutes($nextStart) / 60.0; // always positive hours
    }
}
