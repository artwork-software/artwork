<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\OvertimePayout;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeService
{
    /**
     * Rebuilds the per-day overtime entries for a user from their WorkTimeBookings, applying FIFO
     * compensation: negative-balance days consume the oldest still-open overtime first. Idempotent.
     * Paid-out minutes per entry (manual payouts) are kept and subtracted after the replay.
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

        // 1) Build overtime entries for every positive day.
        $entries = []; // date(string) => ['minutes','remaining','deadline'(Carbon)]
        foreach ($bookings as $booking) {
            $change = (int) $booking->work_time_balance_change;
            if ($change > 0) {
                $dateStr = $booking->booking_day->toDateString();
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

        // 3) Persist each positive entry with its resulting status. Manual payouts are tracked
        //    per entry (paid_out_minutes) and reduce the remaining amount after the replay, so a
        //    recompute never resurrects already paid-out overtime.
        foreach ($entries as $dateStr => $entry) {
            $existingEntry = $existing->get($dateStr);
            $paidOut = (int) ($existingEntry?->paid_out_minutes ?? 0);
            $remaining = max(0, $entry['remaining'] - $paidOut);

            if ($remaining <= 0) {
                $status = $paidOut > 0 ? UserOvertime::STATUS_PAID_OUT : UserOvertime::STATUS_COMPENSATED;
            } else {
                $status = $entry['deadline']->lt($today)
                    ? UserOvertime::STATUS_PAYABLE
                    : UserOvertime::STATUS_OPEN;
            }

            UserOvertime::updateOrCreate(
                ['user_id' => $user->id, 'date' => $dateStr],
                [
                    'minutes' => $entry['minutes'],
                    'remaining_minutes' => $remaining,
                    'deadline' => $entry['deadline']->toDateString(),
                    'status' => $status,
                ]
            );
        }

        // 4) Remove stale open/compensated entries whose day is no longer a positive overtime day.
        //    Never delete payable (must be paid out), paid_out or partially paid entries.
        $validDates = array_keys($entries);
        UserOvertime::forUser($user->id)
            ->whereIn('status', [UserOvertime::STATUS_OPEN, UserOvertime::STATUS_COMPENSATED])
            ->where('paid_out_minutes', 0)
            ->whereNotIn('date', $validDates ?: ['1970-01-01'])
            ->delete();
    }

    /**
     * HR pays out an arbitrary amount of payable overtime. The amount is consumed FIFO from the
     * oldest payable entries, recorded in the payout history and booked against the user's
     * work time balance (the actual payment happens outside artwork).
     *
     * @throws ValidationException when the amount exceeds the payable total
     */
    public function payOut(User $user, int $minutes, int $hrUserId, ?string $comment, ?Carbon $payoutDate = null): OvertimePayout
    {
        $payoutDate ??= Carbon::today();

        return DB::transaction(function () use ($user, $minutes, $hrUserId, $comment, $payoutDate): OvertimePayout {
            $payableEntries = UserOvertime::forUser($user->id)
                ->payable()
                ->orderBy('date')
                ->lockForUpdate()
                ->get();

            $payableTotal = (int) $payableEntries->sum('remaining_minutes');
            if ($minutes > $payableTotal) {
                throw ValidationException::withMessages([
                    'minutes' => __('The amount exceeds the payable overtime.'),
                ]);
            }

            $payout = OvertimePayout::create([
                'user_id' => $user->id,
                'minutes' => $minutes,
                'payout_date' => $payoutDate->toDateString(),
                'created_by' => $hrUserId,
                'comment' => $comment,
            ]);

            $left = $minutes;
            foreach ($payableEntries as $entry) {
                if ($left <= 0) {
                    break;
                }
                $take = min($left, (int) $entry->remaining_minutes);
                $entry->paid_out_minutes += $take;
                $entry->remaining_minutes -= $take;
                if ($entry->remaining_minutes <= 0) {
                    $entry->status = UserOvertime::STATUS_PAID_OUT;
                }
                $entry->paid_out_by = $hrUserId;
                $entry->paid_out_at = now();
                $entry->payout_reason = $comment;
                $entry->save();
                $left -= $take;
            }

            // Zeitkonto um die ausgezahlten Minuten reduzieren.
            app(WorkTimeBookingRepository::class)->updateUserBalance($user, -$minutes);

            return $payout;
        });
    }
}
