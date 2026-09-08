<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Artwork\Modules\WorkTime\Models\OvertimePayout;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeService
{
    public function __construct(private readonly ContractSettingsResolver $contractSettings)
    {
    }

    /**
     * Rebuilds the per-day overtime entries for a user from their WorkTimeBookings, applying FIFO
     * compensation: negative-balance days consume the oldest still-open overtime first. Idempotent.
     * Paid-out minutes per entry (manual payouts) are kept and subtracted after the replay.
     *
     * Vertragshistorie: Überstundenregel und Abbaufrist werden je Buchungstag aus dem an diesem Tag
     * gültigen Vertragszeitraum gelesen (ContractSettingsResolver mit Stichtag), nicht aus dem heute
     * gültigen Satz. Gilt heute kein Zeitraum (Lücke), läuft der Replay trotzdem – Tage ohne aktive
     * Regel oder ohne Frist erzeugen schlicht keinen Eintrag.
     */
    public function recomputeForUser(User $user): void
    {
        // Historie einmalig laden: der Resolver löst danach je Tag ohne weitere Abfrage auf.
        $user->loadMissing('contractAssigns.userContract');
        if ($user->contractAssigns->isEmpty()) {
            return; // nie eine Vertragszuweisung → keine Überstundenregel, nichts zu rechnen
        }
        // Cache des Resolvers ist prozesslokal (Queue-Worker): vor dem Replay leeren, damit eine
        // zwischenzeitlich geänderte Zuweisung nicht mit alten Tageswerten verrechnet wird.
        $this->contractSettings->flush();

        $today = now()->startOfDay();

        $bookings = WorkTimeBooking::where('user_id', $user->id)
            ->whereNotNull('booking_day')
            ->orderBy('booking_day')
            ->get();

        $existing = UserOvertime::forUser($user->id)->get()
            ->keyBy(fn (UserOvertime $e): string => $e->date->toDateString());

        // 1) Build overtime entries for every positive day whose contract period has the rule active.
        $entries = []; // date(string) => ['minutes','remaining','deadline'(Carbon)]
        foreach ($bookings as $booking) {
            $change = (int) $booking->work_time_balance_change;
            if ($change <= 0) {
                continue;
            }

            $day = $booking->booking_day->copy()->startOfDay();
            $period = $this->compensationPeriodOn($user, $day);
            if ($period === null) {
                continue; // an diesem Tag keine aktive Überstundenregel / keine Frist
            }

            $dateStr = $day->toDateString();
            $entries[$dateStr] = [
                'minutes' => $change,
                'remaining' => $change,
                'deadline' => $day->copy()->addDays($period),
            ];
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
        //    Nur Tage, die der Replay tatsächlich beurteilt hat (Überstundenregel an diesem Tag aktiv),
        //    dürfen bereinigt werden: Ist die Regel an einem Tag NICHT aktiv (z. B. Zuweisung heute mit
        //    overtime_rule_active=false, Lücke in der Vertragshistorie), bleibt ein vorhandener Eintrag
        //    unangetastet — sonst löscht jede Buchung/Zuweisung die gesamte offene Historie der Person.
        $staleDates = [];
        foreach ($existing as $dateStr => $existingEntry) {
            if (isset($entries[$dateStr])) {
                continue;
            }
            if (
                !in_array($existingEntry->status, [UserOvertime::STATUS_OPEN, UserOvertime::STATUS_COMPENSATED], true)
                || (int) $existingEntry->paid_out_minutes !== 0
            ) {
                continue;
            }
            if ($this->compensationPeriodOn($user, $existingEntry->date->copy()->startOfDay()) === null) {
                continue; // Tag nicht beurteilt → Eintrag bleibt
            }
            $staleDates[] = $dateStr;
        }

        if ($staleDates !== []) {
            UserOvertime::forUser($user->id)
                ->whereIn('status', [UserOvertime::STATUS_OPEN, UserOvertime::STATUS_COMPENSATED])
                ->where('paid_out_minutes', 0)
                ->whereIn('date', $staleDates)
                ->delete();
        }
    }

    /**
     * Abbaufrist in Tagen für einen Buchungstag: null, wenn an diesem Tag keine Überstundenregel aktiv
     * ist oder keine Frist (> 0) hinterlegt ist – Zuweisung vor Vorlage des am Tag gültigen Zeitraums.
     */
    private function compensationPeriodOn(User $user, Carbon $day): ?int
    {
        if (!$this->contractSettings->bool($user, 'overtime_rule_active', false, $day)) {
            return null;
        }

        $period = $this->contractSettings->int($user, 'overtime_compensation_period', 0, $day);

        return $period > 0 ? $period : null;
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
