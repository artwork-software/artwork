<?php

namespace Tests\Unit\Modules\WorkTime\Support;

use Artwork\Modules\WorkTime\Support\OvertimeLedger;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Kontoprinzip ohne Datenbank: offene Überstunden − Minusstunden = Buchungen − Auszahlungen.
 */
final class OvertimeLedgerTest extends TestCase
{
    private const TODAY = '2026-10-08';

    /**
     * @param array<string, int> $days
     * @param array<int, array{id: int, date: string, minutes: int}> $payouts
     */
    private function ledger(array $days, array $payouts = [], ?int $period = null): OvertimeLedger
    {
        return OvertimeLedger::calculate($days, $payouts, fn (Carbon $day): ?int => $period, Carbon::parse(self::TODAY));
    }

    #[Test]
    #[DataProvider('scenarios')]
    public function overtime_minus_hours_always_equal_the_time_account(array $days, array $payouts, ?int $period): void
    {
        $ledger = $this->ledger($days, $payouts, $period);

        $this->assertSame(
            array_sum($days) - array_sum(array_column($payouts, 'minutes')),
            $ledger->overtimeMinutes() - $ledger->debtMinutes()
        );
        $this->assertSame($ledger->balanceMinutes(), $ledger->overtimeMinutes() - $ledger->debtMinutes());
        $timeline = $ledger->timeline();
        $last = $timeline === [] ? 0 : $timeline[array_key_last($timeline)]['balance_after'];
        $this->assertSame($ledger->balanceMinutes(), $last);
    }

    public static function scenarios(): iterable
    {
        yield 'minus before plus' => [['2026-09-01' => -120, '2026-09-02' => 300], [], null];
        yield 'minus after expired deadline' => [['2026-08-01' => 120, '2026-09-20' => -60], [], 10];
        yield 'payout then retro minus' => [
            ['2026-08-01' => 120, '2026-08-15' => -60, '2026-09-01' => 30],
            [['id' => 1, 'date' => '2026-08-20', 'minutes' => 120]],
            5,
        ];
        yield 'mixed long run' => [
            ['2026-07-01' => 60, '2026-07-02' => -30, '2026-07-03' => -90, '2026-07-10' => 45, '2026-08-01' => 600],
            [['id' => 1, 'date' => '2026-09-01', 'minutes' => 100]],
            14,
        ];
        yield 'nothing' => [[], [], 30];
    }

    #[Test]
    public function minus_hours_are_repaid_before_new_overtime_arises(): void
    {
        $ledger = $this->ledger(['2026-09-01' => -120, '2026-09-02' => 300]);

        $this->assertSame(0, $ledger->debtMinutes());
        $this->assertCount(1, $ledger->accruals());
        $this->assertSame(180, $ledger->accruals()[0]['overtime']);
        $this->assertSame([['date' => '2026-09-01', 'minutes' => 120]], $ledger->accruals()[0]['repaid_debts']);
    }

    #[Test]
    public function the_minus_day_reduces_the_overtime_with_the_earliest_deadline_first(): void
    {
        // 30.01. mit 90 Tagen Frist, 02.02. mit 14 Tagen Frist: das Minus soll die knappere Frist retten
        $periods = ['2026-01-30' => 90, '2026-02-02' => 14];
        $ledger = OvertimeLedger::calculate(
            ['2026-01-30' => 60, '2026-02-02' => 60, '2026-02-10' => -60],
            [],
            fn (Carbon $day): ?int => $periods[$day->toDateString()] ?? null,
            Carbon::parse('2026-03-01')
        );

        [$january, $february] = $ledger->accruals();
        $this->assertSame(60, $january['remaining']);
        $this->assertSame(0, $february['remaining']);
        $this->assertSame(0, $ledger->payableMinutes());
    }

    #[Test]
    public function without_a_rule_nothing_becomes_payable(): void
    {
        $ledger = $this->ledger(['2025-01-01' => 600]);

        $this->assertSame(0, $ledger->payableMinutes());
        $this->assertSame(600, $ledger->openMinutes());
        $this->assertNull($ledger->accruals()[0]['deadline']);
    }

    #[Test]
    public function expired_overtime_and_minus_hours_never_coexist(): void
    {
        $ledger = $this->ledger(['2026-08-01' => 120, '2026-09-20' => -90], [], 10);

        $this->assertSame(30, $ledger->payableMinutes());
        $this->assertSame(0, $ledger->debtMinutes());
        $this->assertSame(30, $ledger->payableNowMinutes());
    }

    #[Test]
    public function a_minus_day_on_the_deadline_still_reduces_the_overtime(): void
    {
        $onDeadline = $this->ledger(['2026-09-01' => 60, '2026-09-11' => -60], [], 10);
        $this->assertSame(OvertimeLedger::STATUS_COMPENSATED, $onDeadline->accruals()[0]['status']);

        // Frist heute (08.10.) = noch offen, gestern = abgelaufen
        $this->assertSame(OvertimeLedger::STATUS_OPEN, $this->ledger(['2026-09-28' => 60], [], 10)->accruals()[0]['status']);
        $this->assertSame(OvertimeLedger::STATUS_PAYABLE, $this->ledger(['2026-09-27' => 60], [], 10)->accruals()[0]['status']);
    }

    #[Test]
    public function a_payout_beyond_the_available_overtime_creates_minus_hours_shown_on_the_payout(): void
    {
        // Auszahlung 120, danach wird der Tag auf +60 korrigiert: 60 Minusstunden aus der Auszahlung
        $ledger = $this->ledger(['2026-09-01' => 60], [['id' => 3, 'date' => '2026-09-20', 'minutes' => 120]], 10);

        $this->assertSame(60, $ledger->debtMinutes());
        $this->assertSame(-60, $ledger->balanceMinutes());
        $debt = $ledger->debts()[0];
        $this->assertSame(['payout', 3], [$debt['source'], $debt['payout_id']]);
    }

    #[Test]
    public function a_payout_uses_expired_overtime_first(): void
    {
        $ledger = $this->ledger(
            ['2026-08-01' => 60, '2026-10-01' => 60],
            [['id' => 7, 'date' => '2026-10-05', 'minutes' => 60]],
            10
        );

        [$expired, $fresh] = $ledger->accruals();
        $this->assertSame(OvertimeLedger::STATUS_PAID_OUT, $expired['status']);
        $this->assertSame(60, $fresh['remaining']);
        $this->assertSame(OvertimeLedger::STATUS_OPEN, $fresh['status']);
        $this->assertSame(60, $ledger->paidOutMinutes());
    }
}
