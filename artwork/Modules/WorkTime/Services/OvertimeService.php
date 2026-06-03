<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;

class OvertimeService
{
    /**
     * Rebuilds the per-day overtime entries for a user from their WorkTimeBookings, applying FIFO
     * compensation: negative-balance days consume the oldest still-open overtime first. Idempotent.
     * paid_out entries are terminal and never touched.
     */
    public function recomputeForUser(User $user): void
    {
        /** @var \Artwork\Modules\User\Models\UserContractAssign|null $assign */
        $assign = $user->contract;
        $period = (int) ($assign?->overtime_compensation_period ?? 0);

        if (!$assign?->overtime_rule_active || $period <= 0) {
            return;
        }

        $today = now()->startOfDay();

        $bookings = WorkTimeBooking::where('user_id', $user->id)
            ->whereNotNull('booking_day')
            ->orderBy('booking_day')
            ->get();

        $existing = UserOvertime::forUser($user->id)->get()
            ->keyBy(fn (UserOvertime $e): string => $e->date->toDateString());

        // 1) Build overtime entries for every positive day (skip days already paid out).
        $entries = []; // date(string) => ['minutes','remaining','deadline'(Carbon)]
        foreach ($bookings as $booking) {
            $dateStr = $booking->booking_day->toDateString();
            $existingEntry = $existing->get($dateStr);

            if ($existingEntry && $existingEntry->status === UserOvertime::STATUS_PAID_OUT) {
                continue; // terminal, does not participate in the replay
            }

            $change = (int) $booking->work_time_balance_change;
            if ($change > 0) {
                $entries[$dateStr] = [
                    'minutes' => $change,
                    'remaining' => $change,
                    'deadline' => $booking->booking_day->copy()->addDays($period),
                ];
            }
        }

        // 2) FIFO: apply negative-balance days (under target) to the oldest open entries.
        foreach ($bookings as $booking) {
            $change = (int) $booking->work_time_balance_change;
            if ($change >= 0) {
                continue;
            }

            $credit = -$change;
            $creditDate = $booking->booking_day;

            foreach ($entries as $dateStr => &$entry) {
                if ($credit <= 0) {
                    break;
                }
                if ($dateStr > $creditDate->toDateString()) {
                    continue; // overtime accrued after this credit day
                }
                if ($entry['deadline']->lt($creditDate)) {
                    continue; // already expired when this credit occurred
                }
                if ($entry['remaining'] <= 0) {
                    continue;
                }
                $take = min($credit, $entry['remaining']);
                $entry['remaining'] -= $take;
                $credit -= $take;
            }
            unset($entry);
        }

        // 3) Persist each positive entry with its resulting status.
        foreach ($entries as $dateStr => $entry) {
            $status = $entry['remaining'] <= 0
                ? UserOvertime::STATUS_COMPENSATED
                : ($entry['deadline']->lt($today) ? UserOvertime::STATUS_PAYABLE : UserOvertime::STATUS_OPEN);

            UserOvertime::updateOrCreate(
                ['user_id' => $user->id, 'date' => $dateStr],
                [
                    'minutes' => $entry['minutes'],
                    'remaining_minutes' => max(0, $entry['remaining']),
                    'deadline' => $entry['deadline']->toDateString(),
                    'status' => $status,
                ]
            );
        }

        // 4) Remove stale open/compensated entries whose day is no longer a positive overtime day.
        //    Never delete payable (must be paid out) or paid_out (terminal) entries.
        $validDates = array_keys($entries);
        UserOvertime::forUser($user->id)
            ->whereIn('status', [UserOvertime::STATUS_OPEN, UserOvertime::STATUS_COMPENSATED])
            ->whereNotIn('date', $validDates ?: ['1970-01-01'])
            ->delete();
    }

    /**
     * HR books out a payable overtime entry (manual action). Only allowed for payable entries.
     */
    public function bookOut(UserOvertime $entry, int $hrUserId, ?string $reason): void
    {
        if (!$entry->isPayable()) {
            return;
        }

        $entry->update([
            'status' => UserOvertime::STATUS_PAID_OUT,
            'paid_out_by' => $hrUserId,
            'paid_out_at' => now(),
            'payout_reason' => $reason,
        ]);
    }
}
