<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Artwork\Modules\WorkTime\Models\OvertimePayout;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Artwork\Modules\WorkTime\Support\OvertimeLedger;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Überstunden nach Kontoprinzip (OvertimeLedger): abgeleitet aus Buchungen und Auszahlungen, die Summe
 * offener Überstunden minus Minusstunden entspricht immer dem Zeitkonto.
 *
 * Die Überstundenregel im Vertrag steuert nur noch Frist, „auszahlbar“ und Auszahlung – ohne Regel
 * werden Überstunden genauso geführt (Auf- und Abbau), nur ohne Frist.
 */
class OvertimeService
{
    public function __construct(private readonly ContractSettingsResolver $contractSettings)
    {
    }

    /**
     * Rechnet das Überstundenkonto einer Person aus Buchungen und Auszahlungen.
     */
    public function ledgerFor(User $user): OvertimeLedger
    {
        // Cache des Resolvers ist prozesslokal (Queue-Worker): vor dem Replay leeren, damit eine
        // zwischenzeitlich geänderte Zuweisung nicht mit alten Tageswerten verrechnet wird.
        $this->contractSettings->flush();
        $user->loadMissing('contractAssigns.userContract');

        $netChangeByDay = WorkTimeBooking::query()
            ->where('user_id', $user->id)
            ->whereNotNull('booking_day')
            ->selectRaw('DATE(booking_day) as day, SUM(work_time_balance_change) as net')
            ->groupBy('day')
            ->pluck('net', 'day')
            ->map(fn ($net): int => (int) $net)
            ->all();

        $payouts = OvertimePayout::query()
            ->where('user_id', $user->id)
            ->orderBy('payout_date')
            ->orderBy('id')
            ->get()
            ->map(fn (OvertimePayout $payout): array => [
                'id' => $payout->id,
                'date' => $payout->payout_date->toDateString(),
                'minutes' => (int) $payout->minutes,
            ])
            ->all();

        return OvertimeLedger::calculate(
            $netChangeByDay,
            $payouts,
            fn (Carbon $day): ?int => $this->compensationPeriodOn($user, $day),
            now()
        );
    }

    /**
     * Schreibt das Überstundenkonto in user_overtimes (ein Eintrag je Tag mit Überstunden): Grundlage für
     * Fristwarnungen (OvertimeDeadlineCheck) und Auszahlung. Idempotent; Tage, die keine Überstunden mehr
     * tragen, werden entfernt – nichts bleibt „eingefroren“ stehen.
     */
    public function recomputeForUser(User $user): void
    {
        if (!WorkTimeAccounting::isEnabled()) {
            return; // Arbeitszeitberechnung aus: Überstunden werden nicht fortgeschrieben
        }

        DB::transaction(function () use ($user): void {
            // Serialisiert mit payOut(): sonst überschreibt ein paralleler Replay eine frische Auszahlung
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $this->persist($user, $this->ledgerFor($user));
        });
    }

    /**
     * HR zahlt einen frei wählbaren Betrag abgelaufener Überstunden aus – höchstens bis zum positiven
     * Kontostand und nur bei heute aktiver Überstundenregel. Der Betrag wird in der Historie geführt und
     * vom Zeitkonto abgezogen (die eigentliche Zahlung erfolgt außerhalb von artwork).
     *
     * @throws ValidationException
     */
    public function payOut(
        User $user,
        int $minutes,
        int $hrUserId,
        ?string $comment,
        ?Carbon $payoutDate = null
    ): OvertimePayout {
        $payoutDate = ($payoutDate ?? Carbon::today())->copy()->startOfDay();

        return DB::transaction(function () use ($user, $minutes, $hrUserId, $comment, $payoutDate): OvertimePayout {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($minutes < 1) {
                throw ValidationException::withMessages([
                    'minutes' => __('Please enter a duration greater than zero.'),
                ]);
            }
            // Nur heute: die Prüfung rechnet mit „heute auszahlbar“; ein rückdatierter Betrag würde im Verlauf an
            // einem Tag verrechnet, an dem diese Stunden noch gar nicht abgelaufen waren
            if (!$payoutDate->isSameDay(Carbon::today())) {
                throw ValidationException::withMessages([
                    'payout_date' => __('Overtime can only be paid out with today\'s date.'),
                ]);
            }
            if (!$this->ruleActiveToday($user)) {
                throw ValidationException::withMessages([
                    'minutes' => __('Overtime can only be paid out with an active overtime rule.'),
                ]);
            }

            $ledger = $this->ledgerFor($user);
            if ($minutes > $ledger->payableNowMinutes()) {
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

            // Zeitkonto um die ausgezahlten Minuten reduzieren.
            app(WorkTimeBookingRepository::class)->updateUserBalance($user, -$minutes);

            $this->persist($user, $this->ledgerFor($user));

            return $payout;
        });
    }

    /**
     * Überstundenregel und Frist heute (Anzeige/Auszahlung).
     *
     * @return array{rule_active: bool, compensation_period: int|null}
     */
    public function ruleSettingsToday(User $user): array
    {
        $this->contractSettings->flush();
        $active = $this->ruleActiveToday($user);
        $period = $active ? $this->contractSettings->int($user, 'overtime_compensation_period', 0, Carbon::today()) : 0;

        return ['rule_active' => $active, 'compensation_period' => $period > 0 ? $period : null];
    }

    private function ruleActiveToday(User $user): bool
    {
        return $this->contractSettings->assignFor($user, Carbon::today()) !== null
            && $this->contractSettings->bool($user, 'overtime_rule_active', false, Carbon::today());
    }

    /**
     * Abbaufrist in Tagen für einen Buchungstag: null, wenn an diesem Tag keine Überstundenregel aktiv
     * ist oder keine Frist (> 0) hinterlegt ist – dann laufen die Überstunden ohne Frist.
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
     * Schreibt nur Abweichungen: vorhandene Einträge einmal laden und vergleichen (statt je Tag eine Abfrage),
     * neue Tage gesammelt einfügen, Tage ohne Überstunden entfernen.
     */
    private function persist(User $user, OvertimeLedger $ledger): void
    {
        $payouts = OvertimePayout::query()->where('user_id', $user->id)->get()->keyBy('id');
        $existing = UserOvertime::forUser($user->id)->get()
            ->keyBy(fn (UserOvertime $entry): string => $entry->date->toDateString());

        $keepDates = [];
        $inserts = [];
        $now = now();
        foreach ($ledger->accruals() as $accrual) {
            $keepDates[$accrual['date']] = true;
            $lastPayout = null;
            foreach ($accrual['used_by'] as $use) {
                if ($use['type'] === 'payout') {
                    $lastPayout = $payouts->get($use['payout_id'] ?? null) ?? $lastPayout;
                }
            }

            $attributes = [
                'minutes' => $accrual['overtime'],
                'remaining_minutes' => $accrual['remaining'],
                'paid_out_minutes' => $accrual['paid_out'],
                'deadline' => $accrual['deadline'],
                'status' => $accrual['status'],
                'paid_out_by' => $lastPayout?->created_by,
                'paid_out_at' => $lastPayout?->created_at,
                'payout_reason' => $lastPayout?->comment,
            ];

            $entry = $existing->get($accrual['date']);
            if ($entry === null) {
                $inserts[] = $attributes + [
                    'user_id' => $user->id,
                    'date' => $accrual['date'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                continue;
            }

            $entry->fill($attributes);
            if ($entry->isDirty()) {
                $entry->save();
            }
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            UserOvertime::query()->insert($chunk);
        }

        $staleIds = $existing
            ->keys()
            ->reject(fn (string $date): bool => isset($keepDates[$date]))
            ->map(fn (string $date): int => $existing[$date]->id);
        if ($staleIds->isNotEmpty()) {
            UserOvertime::query()->whereIn('id', $staleIds)->delete();
        }
    }
}
