<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Shift\Models\UserShiftKpiSnapshot;
use Artwork\Modules\Shift\Services\ShiftKpiTrackingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Trackt die spielzeitbezogenen Kennzahlen je Schicht-User und speichert sie als Snapshot (DP-18).
 * Läuft nächtlich nach der Arbeitszeitberechnung. Voll-Recompute (idempotenter Upsert).
 */
class TrackShiftKpisCommand extends Command
{
    protected $signature = 'artwork:track-shift-kpis';

    protected $description = 'Track season-related shift KPIs per user and store a snapshot';

    public function handle(ShiftKpiTrackingService $service): int
    {
        $this->info('Tracking shift KPIs...');

        [$seasonStart, $seasonEnd] = $service->getSeasonBounds();

        $users = User::query()
            ->where('can_work_shifts', true)
            ->with(['individualTimes'])
            ->get();

        $now = Carbon::now();
        $count = 0;

        foreach ($users as $user) {
            $kpis = $service->computeForUser($user, $seasonStart, $seasonEnd);

            UserShiftKpiSnapshot::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'season_start' => $kpis['season_start'],
                    'season_end' => $kpis['season_end'],
                ],
                [
                    'calendar_year' => $kpis['calendar_year'],
                    'free_sundays_sat_mon_half1' => $kpis['free_sundays_sat_mon_half1'],
                    'free_sundays_sat_mon_half2' => $kpis['free_sundays_sat_mon_half2'],
                    'free_sundays_and_saturdays_season' => $kpis['free_sundays_and_saturdays_season'],
                    'free_sundays_calendar_year' => $kpis['free_sundays_calendar_year'],
                    'free_sundays_per_season' => $kpis['free_sundays_per_season'],
                    'one_and_half_combos_half1' => $kpis['one_and_half_combos_half1'],
                    'one_and_half_combos_half2' => $kpis['one_and_half_combos_half2'],
                    'granted_half_free_days_half1' => $kpis['granted_half_free_days_half1'],
                    'granted_half_free_days_half2' => $kpis['granted_half_free_days_half2'],
                    'days_off_first_26_weeks_count' => $kpis['days_off_first_26_weeks_count'],
                    'granted_vacation_days_year' => $kpis['granted_vacation_days_year'],
                    'recalculated_at' => $now,
                ]
            );
            $count++;
        }

        $this->info("Shift KPIs tracked for {$count} users.");

        return self::SUCCESS;
    }
}
