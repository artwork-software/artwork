<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\OvertimePayout;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;

/**
 * Überstunden-Auswertung (DP-18 Stufe 2).
 *
 * Überstunde = positiver Anteil des Zeitkontos (`work_time_balance`), rekonstruiert aus den
 * chronologischen Tages-Deltas (`work_time_bookings.work_time_balance_change`, Minuten).
 * Minus-Tage (inkl. Nicht-Holiday-Ausgleichstage) UND als negative Zeitkorrektur gebuchte
 * Auszahlungen bauen die ältesten Überstunden-Chunks zuerst ab (FIFO).
 *
 * Leichtgewichtig: keine Chunk-Tabelle — alles live aus den Buchungen berechnet.
 */
class OvertimeService
{
    public function computeForUser(User $user): array
    {
        $contract = $user->activeWorkContract();
        $deadlineDays = (int) ($contract->overtime_deadline_days ?? 0);
        $payoutActive = (bool) ($contract->overtime_payout_active ?? false);

        // Zeitleiste aus Tages-Buchungen (Delta) + manuellen Auszahlungen (negatives Delta) bauen.
        $timeline = [];
        foreach (
            WorkTimeBooking::query()
                ->where('user_id', $user->id)
                ->get(['booking_day', 'work_time_balance_change']) as $booking
        ) {
            $delta = (int) $booking->work_time_balance_change;
            if ($delta !== 0) {
                $timeline[] = ['date' => Carbon::parse($booking->booking_day)->toDateString(), 'delta' => $delta];
            }
        }
        foreach (
            OvertimePayout::query()
                ->where('user_id', $user->id)
                ->get(['payout_date', 'minutes']) as $payout
        ) {
            $minutes = (int) $payout->minutes;
            if ($minutes !== 0) {
                $timeline[] = ['date' => Carbon::parse($payout->payout_date)->toDateString(), 'delta' => -abs($minutes)];
            }
        }
        usort($timeline, fn ($a, $b) => $a['date'] <=> $b['date']);

        $running = 0;
        $queue = [];   // FIFO: ['date' => string, 'remaining' => int, 'deadline' => ?string]
        $perDay = [];  // booking_day => zugewachsene Überstunden-Minuten

        foreach ($timeline as $point) {
            $delta = $point['delta'];
            if ($delta === 0) {
                continue;
            }
            $before = $running;
            $after = $running + $delta;
            $running = $after;

            if ($delta > 0) {
                $overtimeAdded = max(0, $after) - max(0, $before);
                if ($overtimeAdded > 0) {
                    $accrual = Carbon::parse($point['date']);
                    $queue[] = [
                        'date' => $accrual->toDateString(),
                        'remaining' => $overtimeAdded,
                        'deadline' => $deadlineDays > 0
                            ? $accrual->copy()->addDays($deadlineDays)->toDateString()
                            : null,
                    ];
                    $perDay[$accrual->toDateString()] = ($perDay[$accrual->toDateString()] ?? 0) + $overtimeAdded;
                }
            } else {
                // Abbau des positiven Anteils -> älteste Chunks zuerst
                $reduction = max(0, $before) - max(0, $after);
                foreach ($queue as &$chunk) {
                    if ($reduction <= 0) {
                        break;
                    }
                    if ($chunk['remaining'] <= 0) {
                        continue;
                    }
                    $take = min($chunk['remaining'], $reduction);
                    $chunk['remaining'] -= $take;
                    $reduction -= $take;
                }
                unset($chunk);
            }
        }

        $today = Carbon::today();
        $openChunks = [];
        $payable = 0;
        foreach ($queue as $chunk) {
            if ($chunk['remaining'] <= 0) {
                continue;
            }
            $overdue = $payoutActive
                && $chunk['deadline'] !== null
                && Carbon::parse($chunk['deadline'])->lt($today);
            if ($overdue) {
                $payable += $chunk['remaining'];
            }
            $openChunks[] = [
                'accrual_date' => $chunk['date'],
                'remaining_minutes' => $chunk['remaining'],
                'deadline' => $chunk['deadline'],
                'overdue' => $overdue,
            ];
        }

        // perDay als absteigend sortierte Liste (neueste zuerst)
        krsort($perDay);
        $perDayList = [];
        foreach ($perDay as $date => $minutes) {
            $perDayList[] = ['date' => $date, 'minutes' => $minutes];
        }

        return [
            'balance_minutes' => max(0, $running),
            'raw_balance_minutes' => $running,
            'payable_minutes' => $payable,
            'payout_active' => $payoutActive,
            'deadline_days' => $deadlineDays,
            'open_chunks' => $openChunks,
            'per_day' => $perDayList,
        ];
    }
}
