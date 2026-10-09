<?php

namespace Artwork\Modules\WorkTime\Support;

use Carbon\Carbon;

/**
 * Überstundenkonto nach Kontoprinzip – reine Rechnung ohne Datenbankzugriff.
 *
 * Eingaben sind die Netto-Saldoänderung je Buchungstag (Summe aller Buchungszeilen des Tages) und die
 * Auszahlungen. Chronologisch gilt:
 *  - Plustag: gleicht zuerst offene Minusstunden aus (älteste zuerst); erst der Rest sind Überstunden.
 *    Mit aktiver Überstundenregel bekommt dieser Rest eine Abbaufrist (Tag + Frist in Tagen).
 *  - Minustag: baut offene Überstunden ab – zuerst noch nicht abgelaufene (früheste Frist zuerst, ohne
 *    Frist danach), zuletzt bereits abgelaufene; was nicht gedeckt ist, wird zu Minusstunden. Damit
 *    gibt es nie gleichzeitig offene Überstunden und Minusstunden aus Tagen.
 *  - Auszahlung: verbraucht zuerst abgelaufene (auszahlbare) Überstunden, dann übrige offene; was
 *    nicht gedeckt ist (z. B. rückwirkende Korrektur nach der Auszahlung), wird zu Minusstunden.
 *
 * Damit gilt immer: Summe offener Überstunden − Summe offener Minusstunden
 *                 = Summe aller Buchungen − Summe aller Auszahlungen (= Zeitkonto).
 */
final class OvertimeLedger
{
    public const STATUS_OPEN = 'open';
    public const STATUS_COMPENSATED = 'compensated';
    public const STATUS_PAYABLE = 'payable';
    public const STATUS_PAID_OUT = 'paid_out';

    /**
     * @var array<int, array{
     *     date: string, change: int, overtime: int, remaining: int, paid_out: int, compensated: int,
     *     deadline: string|null, status: string,
     *     repaid_debts: array<int, array{date: string, minutes: int}>,
     *     used_by: array<int, array{date: string, minutes: int, type: string, payout_id?: int|string}>
     * }>
     */
    private array $accruals = [];

    /**
     * @var array<int, array{
     *     date: string, source: string, minutes: int, remaining: int, payout_id?: int|string,
     *     repaid_by: array<int, array{date: string, minutes: int}>
     * }>
     */
    private array $debts = [];

    /**
     * Chronologische Ereignisse (Tage mit Saldoänderung und Auszahlungen) inkl. Kontostand danach.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $timeline = [];

    private int $bookedTotal = 0;

    private int $paidOutTotal = 0;

    /**
     * @param array<string, int> $netChangeByDay 'Y-m-d' => Netto-Minuten des Tages
     * @param array<int, array{id: int|string, date: string, minutes: int}> $payouts
     * @param callable(Carbon): ?int $compensationPeriodOn Abbaufrist in Tagen am Tag, null = keine Regel/Frist
     */
    public static function calculate(
        array $netChangeByDay,
        array $payouts,
        callable $compensationPeriodOn,
        Carbon $today
    ): self {
        $ledger = new self();
        $ledger->run($netChangeByDay, $payouts, $compensationPeriodOn, $today->copy()->startOfDay());

        return $ledger;
    }

    /**
     * @param array<string, int> $netChangeByDay
     * @param array<int, array{id: int|string, date: string, minutes: int}> $payouts
     */
    private function run(array $netChangeByDay, array $payouts, callable $compensationPeriodOn, Carbon $today): void
    {
        $payoutsByDay = [];
        foreach ($payouts as $payout) {
            $payoutsByDay[$payout['date']][] = $payout;
        }

        $days = array_unique(array_merge(array_keys($netChangeByDay), array_keys($payoutsByDay)));
        sort($days);

        $balance = 0;
        foreach ($days as $day) {
            // Auszahlungen laufen tagsüber, die Tagesbuchung erst um 23:59
            foreach ($payoutsByDay[$day] ?? [] as $payout) {
                $minutes = max(0, (int) $payout['minutes']);
                $this->paidOutTotal += $minutes;
                $balance -= $minutes;
                $used = $this->applyPayout($day, $minutes, $payout['id']);
                $this->timeline[] = [
                    'type' => 'payout',
                    'date' => $day,
                    'payout_id' => $payout['id'],
                    'change' => -$minutes,
                    'used' => $used['used'],
                    'debt_created' => $used['debt'],
                    'balance_after' => $balance,
                ];
            }

            $change = (int) ($netChangeByDay[$day] ?? 0);
            if ($change === 0) {
                continue;
            }
            $this->bookedTotal += $change;
            $balance += $change;

            if ($change > 0) {
                $event = $this->applyPlus($day, $change, $compensationPeriodOn);
            } else {
                $event = $this->applyMinus($day, -$change);
            }
            $this->timeline[] = $event + ['date' => $day, 'change' => $change, 'balance_after' => $balance];
        }

        foreach ($this->accruals as &$accrual) {
            $accrual['status'] = $this->statusFor($accrual, $today);
        }
        unset($accrual);
    }

    /**
     * @return array<string, mixed>
     */
    private function applyPlus(string $day, int $minutes, callable $compensationPeriodOn): array
    {
        $repaid = [];
        foreach ($this->debts as &$debt) {
            if ($minutes <= 0) {
                break;
            }
            if ($debt['remaining'] <= 0) {
                continue;
            }
            $take = min($minutes, $debt['remaining']);
            $debt['remaining'] -= $take;
            $debt['repaid_by'][] = ['date' => $day, 'minutes' => $take];
            $repaid[] = ['date' => $debt['date'], 'minutes' => $take];
            $minutes -= $take;
        }
        unset($debt);

        $deadline = null;
        if ($minutes > 0) {
            $period = $compensationPeriodOn(Carbon::parse($day)->startOfDay());
            $deadline = $period !== null && $period > 0
                ? Carbon::parse($day)->addDays($period)->toDateString()
                : null;
            $this->accruals[] = [
                'date' => $day,
                'change' => $minutes + array_sum(array_column($repaid, 'minutes')),
                'overtime' => $minutes,
                'remaining' => $minutes,
                'paid_out' => 0,
                'compensated' => 0,
                'deadline' => $deadline,
                'status' => self::STATUS_OPEN,
                'repaid_debts' => $repaid,
                'used_by' => [],
            ];
        }

        return [
            'type' => 'plus',
            'repaid_debts' => $repaid,
            'overtime' => max(0, $minutes),
            'deadline' => $deadline,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyMinus(string $day, int $minutes): array
    {
        $candidates = [];
        $expired = [];
        foreach ($this->accruals as $index => $accrual) {
            if ($accrual['remaining'] <= 0) {
                continue;
            }
            if ($accrual['deadline'] !== null && $accrual['deadline'] < $day) {
                $expired[] = $index;
                continue;
            }
            $candidates[] = $index;
        }
        // Früheste Frist zuerst (verhindert unnötig auszahlbare Stunden), ohne Frist zuletzt, dann Datum
        usort($candidates, function (int $a, int $b): int {
            $deadlineA = $this->accruals[$a]['deadline'] ?? '9999-12-31';
            $deadlineB = $this->accruals[$b]['deadline'] ?? '9999-12-31';

            return [$deadlineA, $this->accruals[$a]['date']] <=> [$deadlineB, $this->accruals[$b]['date']];
        });
        // Abgelaufene (auszahlbare) Überstunden erst zuletzt, aber vor neuen Minusstunden: sonst stünden
        // „auszahlbar“ und Minusstunden nebeneinander bei Kontostand 0 – weder abbau- noch auszahlbar
        $candidates = array_merge($candidates, $expired);

        $compensated = [];
        foreach ($candidates as $index) {
            if ($minutes <= 0) {
                break;
            }
            $take = min($minutes, $this->accruals[$index]['remaining']);
            $this->accruals[$index]['remaining'] -= $take;
            $this->accruals[$index]['compensated'] += $take;
            $this->accruals[$index]['used_by'][] = ['date' => $day, 'minutes' => $take, 'type' => 'compensation'];
            $compensated[] = ['date' => $this->accruals[$index]['date'], 'minutes' => $take];
            $minutes -= $take;
        }

        if ($minutes > 0) {
            $this->debts[] = [
                'date' => $day,
                'source' => 'day',
                'minutes' => $minutes,
                'remaining' => $minutes,
                'repaid_by' => [],
            ];
        }

        return [
            'type' => 'minus',
            'compensated' => $compensated,
            'debt_created' => max(0, $minutes),
        ];
    }

    /**
     * @return array{used: array<int, array{date: string, minutes: int}>, debt: int}
     */
    private function applyPayout(string $day, int $minutes, int|string $payoutId): array
    {
        $payable = [];
        $other = [];
        foreach ($this->accruals as $index => $accrual) {
            if ($accrual['remaining'] <= 0) {
                continue;
            }
            if ($accrual['deadline'] !== null && $accrual['deadline'] < $day) {
                $payable[] = $index;
            } else {
                $other[] = $index;
            }
        }

        $used = [];
        foreach (array_merge($payable, $other) as $index) {
            if ($minutes <= 0) {
                break;
            }
            $take = min($minutes, $this->accruals[$index]['remaining']);
            $this->accruals[$index]['remaining'] -= $take;
            $this->accruals[$index]['paid_out'] += $take;
            $this->accruals[$index]['used_by'][] = [
                'date' => $day,
                'minutes' => $take,
                'type' => 'payout',
                'payout_id' => $payoutId,
            ];
            $used[] = ['date' => $this->accruals[$index]['date'], 'minutes' => $take];
            $minutes -= $take;
        }

        if ($minutes > 0) {
            $this->debts[] = [
                'date' => $day,
                'payout_id' => $payoutId,
                'source' => 'payout',
                'minutes' => $minutes,
                'remaining' => $minutes,
                'repaid_by' => [],
            ];
        }

        return ['used' => $used, 'debt' => max(0, $minutes)];
    }

    /**
     * @param array<string, mixed> $accrual
     */
    private function statusFor(array $accrual, Carbon $today): string
    {
        if ($accrual['remaining'] <= 0) {
            return $accrual['paid_out'] > 0 ? self::STATUS_PAID_OUT : self::STATUS_COMPENSATED;
        }

        return $accrual['deadline'] !== null && $accrual['deadline'] < $today->toDateString()
            ? self::STATUS_PAYABLE
            : self::STATUS_OPEN;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function accruals(): array
    {
        return $this->accruals;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function debts(): array
    {
        return $this->debts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timeline(): array
    {
        return $this->timeline;
    }

    public function openMinutes(): int
    {
        return $this->sumRemaining(self::STATUS_OPEN);
    }

    public function payableMinutes(): int
    {
        return $this->sumRemaining(self::STATUS_PAYABLE);
    }

    public function overtimeMinutes(): int
    {
        return (int) array_sum(array_column($this->accruals, 'remaining'));
    }

    public function debtMinutes(): int
    {
        return (int) array_sum(array_column($this->debts, 'remaining'));
    }

    public function paidOutMinutes(): int
    {
        return $this->paidOutTotal;
    }

    /**
     * Kontostand laut Buchungen und Auszahlungen (= offene Überstunden − Minusstunden).
     */
    public function balanceMinutes(): int
    {
        return $this->bookedTotal - $this->paidOutTotal;
    }

    /**
     * Höchstens auszahlbar: abgelaufene Überstunden, aber nie mehr als der positive Kontostand
     * (sonst rutscht das Zeitkonto durch die Auszahlung ins Minus).
     */
    public function payableNowMinutes(): int
    {
        return max(0, min($this->payableMinutes(), $this->balanceMinutes()));
    }

    private function sumRemaining(string $status): int
    {
        $sum = 0;
        foreach ($this->accruals as $accrual) {
            if ($accrual['status'] === $status) {
                $sum += $accrual['remaining'];
            }
        }

        return $sum;
    }
}
