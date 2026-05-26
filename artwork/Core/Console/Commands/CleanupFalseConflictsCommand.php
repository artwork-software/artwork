<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupFalseConflictsCommand extends Command
{
    protected $signature = 'artwork:cleanup-false-conflicts';
    protected $description = 'Removes vacation and availability conflict records where the date does not match the shift date range';

    public function handle(): void
    {
        $this->info('--- Cleaning up false conflict records ---');

        $deletedVacationConflicts = DB::table('vacation_conflicts')
            ->join('vacations', 'vacation_conflicts.vacation_id', '=', 'vacations.id')
            ->join('shifts', 'vacation_conflicts.shift_id', '=', 'shifts.id')
            ->where(function ($query): void {
                $query->whereColumn('vacations.date', '<', DB::raw('DATE(shifts.start_date)'))
                    ->orWhereColumn('vacations.date', '>', DB::raw('DATE(shifts.end_date)'));
            })
            ->where(function ($query): void {
                $query->whereNull('shifts.event_start_day')
                    ->orWhereColumn('vacations.date', '!=', 'shifts.event_start_day');
            })
            ->delete();

        $this->info("Deleted {$deletedVacationConflicts} false vacation conflict(s).");

        $deletedAvailabilityConflicts = DB::table('availabilities_conflicts')
            ->join('availabilities', 'availabilities_conflicts.availability_id', '=', 'availabilities.id')
            ->join('shifts', 'availabilities_conflicts.shift_id', '=', 'shifts.id')
            ->where(function ($query): void {
                $query->whereColumn('availabilities.date', '<', DB::raw('DATE(shifts.start_date)'))
                    ->orWhereColumn('availabilities.date', '>', DB::raw('DATE(shifts.end_date)'));
            })
            ->where(function ($query): void {
                $query->whereNull('shifts.event_start_day')
                    ->orWhereColumn('availabilities.date', '!=', 'shifts.event_start_day');
            })
            ->delete();

        $this->info("Deleted {$deletedAvailabilityConflicts} false availability conflict(s).");

        $this->info('--- Cleanup finished ---');
    }
}
