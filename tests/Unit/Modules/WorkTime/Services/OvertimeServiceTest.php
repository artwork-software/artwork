<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\WorkTime\Models\OvertimePayout;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OvertimeServiceTest extends TestCase
{
    private OvertimeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OvertimeService::class);
    }

    private function userWithContract(bool $active = true, ?int $period = 30): User
    {
        $user = User::factory()->create();
        UserContractAssign::factory()->create([
            'user_id' => $user->id,
            'overtime_rule_active' => $active,
            'overtime_compensation_period' => $period,
        ]);

        return $user->fresh();
    }

    private function booking(User $user, Carbon $day, int $balanceChange): void
    {
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'name' => 'test_' . $day->toDateString(),
            'booking_day' => $day->toDateString(),
            'booking_weekday' => $day->dayOfWeek,
            'wanted_working_hours' => 0,
            'worked_hours' => max(0, $balanceChange),
            'work_time_balance_change' => $balanceChange,
        ]);
    }

    #[Test]
    public function positive_day_creates_open_entry_with_deadline(): void
    {
        $user = $this->userWithContract(period: 30);
        $day = Carbon::now()->startOfDay();
        $this->booking($user, $day, 120);

        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->whereDate('date', $day)->first();
        $this->assertNotNull($entry);
        $this->assertSame(120, $entry->minutes);
        $this->assertSame(120, $entry->remaining_minutes);
        $this->assertSame(UserOvertime::STATUS_OPEN, $entry->status);
        $this->assertSame($day->copy()->addDays(30)->toDateString(), $entry->deadline->toDateString());
    }

    #[Test]
    public function bookings_of_the_same_day_are_summed_into_one_overtime_entry(): void
    {
        // Regression: Tages- und Korrekturbuchung desselben Tages sind eigene Zeilen; die Überstunde
        // nahm nur eine davon (je nach Reihenfolge) statt der Summe wie im Saldo.
        $user = $this->userWithContract(period: 30);
        $day = Carbon::now()->startOfDay()->subDay();
        $this->booking($user, $day, 30);
        $this->booking($user, $day, 60);

        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->sole();
        $this->assertSame($day->toDateString(), $entry->date->toDateString());
        $this->assertSame(90, $entry->minutes);
        $this->assertSame(90, $entry->remaining_minutes);
    }

    #[Test]
    public function mixed_signs_on_the_same_day_are_netted(): void
    {
        $user = $this->userWithContract(period: 30);
        $older = Carbon::now()->startOfDay()->subDays(3);
        $mixedPositive = Carbon::now()->startOfDay()->subDays(2);
        $mixedNegative = Carbon::now()->startOfDay()->subDay();
        $this->booking($user, $older, 60);
        // +120 und −30 am selben Tag -> Überstunde 90, die ältere bleibt unangetastet
        $this->booking($user, $mixedPositive, 120);
        $this->booking($user, $mixedPositive, -30);
        // +20 und −50 am selben Tag -> netto −30 baut FIFO die älteste Überstunde ab
        $this->booking($user, $mixedNegative, 20);
        $this->booking($user, $mixedNegative, -50);

        $this->service->recomputeForUser($user);

        $entries = UserOvertime::where('user_id', $user->id)->orderBy('date')->get()
            ->mapWithKeys(fn (UserOvertime $e): array => [
                $e->date->toDateString() => [$e->minutes, $e->remaining_minutes],
            ])
            ->all();
        $this->assertSame([
            $older->toDateString() => [60, 30],
            $mixedPositive->toDateString() => [90, 90],
        ], $entries);
    }

    #[Test]
    public function negative_day_fifo_consumes_overtime_until_compensated(): void
    {
        $user = $this->userWithContract(period: 30);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(2), 120);
        $this->booking($user, Carbon::now()->startOfDay()->subDay(), -120);

        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->first();
        $this->assertSame(0, $entry->remaining_minutes);
        $this->assertSame(UserOvertime::STATUS_COMPENSATED, $entry->status);
    }

    #[Test]
    public function partial_offset_keeps_entry_open_with_reduced_remaining(): void
    {
        $user = $this->userWithContract(period: 30);
        $this->booking($user, Carbon::now()->startOfDay()->subDay(), 120);
        $this->booking($user, Carbon::now()->startOfDay(), -50);

        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->first();
        $this->assertSame(70, $entry->remaining_minutes);
        $this->assertSame(UserOvertime::STATUS_OPEN, $entry->status);
    }

    #[Test]
    public function expired_unoffset_overtime_becomes_payable(): void
    {
        $user = $this->userWithContract(period: 5);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(10), 60);

        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->first();
        $this->assertSame(60, $entry->remaining_minutes);
        $this->assertSame(UserOvertime::STATUS_PAYABLE, $entry->status);
    }

    #[Test]
    public function inactive_rule_tracks_overtime_without_deadline(): void
    {
        // Kontoprinzip: Überstunden werden auch ohne Regel geführt – nur ohne Frist (nie auszahlbar)
        $user = $this->userWithContract(active: false, period: 30);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(60), 120);

        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->sole();
        $this->assertNull($entry->deadline);
        $this->assertSame(120, $entry->remaining_minutes);
        $this->assertSame(UserOvertime::STATUS_OPEN, $entry->status);
    }

    #[Test]
    public function plus_hours_first_compensate_open_minus_hours(): void
    {
        $user = $this->userWithContract(period: 30);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(5), -120);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(2), 180);

        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->sole();
        $this->assertSame(60, $entry->minutes);
        $this->assertSame(60, $entry->remaining_minutes);
        $ledger = $this->service->ledgerFor($user);
        $this->assertSame(0, $ledger->debtMinutes());
        $this->assertSame(60, $ledger->balanceMinutes());
    }

    #[Test]
    public function expired_overtime_is_reduced_last_instead_of_creating_minus_hours(): void
    {
        // Sonst stünden 120 „auszahlbar“ und 30 Minusstunden nebeneinander – nicht auszahl- und nicht abbaubar
        $user = $this->userWithContract(period: 5);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(20), 120); // Frist abgelaufen
        $this->booking($user, Carbon::now()->startOfDay()->subDays(2), -30);

        $ledger = $this->service->ledgerFor($user);

        $this->assertSame(90, $ledger->payableMinutes());
        $this->assertSame(0, $ledger->debtMinutes());
        $this->assertSame(90, $ledger->balanceMinutes());
        $this->assertSame(90, $ledger->payableNowMinutes());
    }

    #[Test]
    public function payouts_are_replayed_from_the_payout_history(): void
    {
        $user = $this->userWithContract(period: 30);
        $day = Carbon::now()->startOfDay()->subDays(3);
        $this->booking($user, $day, 90);
        OvertimePayout::create([
            'user_id' => $user->id,
            'minutes' => 90,
            'payout_date' => Carbon::today()->toDateString(),
            'created_by' => User::factory()->create()->id,
        ]);

        $this->service->recomputeForUser($user);
        $this->service->recomputeForUser($user);

        $entry = UserOvertime::where('user_id', $user->id)->sole();
        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $entry->status);
        $this->assertSame(0, $entry->remaining_minutes);
        $this->assertSame(90, $entry->paid_out_minutes);
    }

    #[Test]
    public function pay_out_consumes_payable_entries_fifo_and_reduces_balance(): void
    {
        $user = $this->userWithContract(period: 5);
        $user->update(['work_time_balance' => 150]);
        $hr = User::factory()->create();
        $this->booking($user, Carbon::now()->startOfDay()->subDays(20), 60);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(15), 90);

        $this->service->payOut($user, 100, $hr->id, 'Paid with March payroll');

        $entries = UserOvertime::where('user_id', $user->id)->orderBy('date')->get();
        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $entries[0]->status);
        $this->assertSame(0, $entries[0]->remaining_minutes);
        $this->assertSame(60, $entries[0]->paid_out_minutes);
        $this->assertSame($hr->id, $entries[0]->paid_out_by);
        $this->assertSame(UserOvertime::STATUS_PAYABLE, $entries[1]->status);
        $this->assertSame(50, $entries[1]->remaining_minutes);
        $this->assertSame(40, $entries[1]->paid_out_minutes);

        $payout = OvertimePayout::where('user_id', $user->id)->sole();
        $this->assertSame(100, $payout->minutes);
        $this->assertSame($hr->id, $payout->created_by);
        $this->assertSame('Paid with March payroll', $payout->comment);

        $this->assertSame(50, (int) $user->fresh()->work_time_balance);
    }

    #[Test]
    public function pay_out_rejects_amount_exceeding_payable_total(): void
    {
        $user = $this->userWithContract(period: 5);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(20), 60);

        $this->expectException(ValidationException::class);

        $this->service->payOut($user, 61, User::factory()->create()->id, null);
    }

    #[Test]
    public function pay_out_only_accepts_todays_date(): void
    {
        $user = $this->userWithContract(period: 5);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(20), 60);

        $this->expectException(ValidationException::class);

        $this->service->payOut($user, 30, User::factory()->create()->id, null, Carbon::yesterday());
    }

    #[Test]
    public function two_payouts_on_one_day_keep_their_own_metadata(): void
    {
        $user = $this->userWithContract(period: 5);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->booking($user, Carbon::now()->startOfDay()->subDays(20), 60);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(19), 30);

        $this->service->payOut($user, 60, $first->id, 'erste');
        $this->service->payOut($user, 30, $second->id, 'zweite');

        $entries = UserOvertime::where('user_id', $user->id)->orderBy('date')->get();
        $this->assertSame([$first->id, 'erste'], [$entries[0]->paid_out_by, $entries[0]->payout_reason]);
        $this->assertSame([$second->id, 'zweite'], [$entries[1]->paid_out_by, $entries[1]->payout_reason]);
    }

    #[Test]
    public function pay_out_requires_an_active_overtime_rule(): void
    {
        $user = $this->userWithContract(active: false, period: 5);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(20), 60);

        $this->expectException(ValidationException::class);

        $this->service->payOut($user, 10, User::factory()->create()->id, null);
    }

    #[Test]
    public function pay_out_does_not_touch_open_entries(): void
    {
        $user = $this->userWithContract(period: 5);
        $this->booking($user, Carbon::now()->startOfDay()->subDays(20), 60);
        $this->booking($user, Carbon::now()->startOfDay(), 120);

        $this->service->payOut($user, 60, User::factory()->create()->id, null);

        $entries = UserOvertime::where('user_id', $user->id)->orderBy('date')->get();
        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $entries[0]->status);
        $this->assertSame(UserOvertime::STATUS_OPEN, $entries[1]->status);
        $this->assertSame(120, $entries[1]->remaining_minutes);
        $this->assertSame(0, $entries[1]->paid_out_minutes);
    }

    #[Test]
    public function a_payable_entry_disappears_when_its_day_is_corrected_to_zero(): void
    {
        // Vorher blieb ein auszahlbarer Eintrag "eingefroren" stehen, obwohl der Tag korrigiert war
        $user = $this->userWithContract(period: 5);
        $day = Carbon::now()->startOfDay()->subDays(20);
        $this->booking($user, $day, 120);
        $this->service->recomputeForUser($user);
        $this->assertSame(UserOvertime::STATUS_PAYABLE, UserOvertime::where('user_id', $user->id)->sole()->status);

        $this->booking($user, $day, -120);
        $this->service->recomputeForUser($user);

        $this->assertSame(0, UserOvertime::where('user_id', $user->id)->count());
    }

    #[Test]
    public function an_active_rule_still_removes_the_stale_entry_of_a_day_without_overtime(): void
    {
        $user = $this->userWithContract(active: true, period: 30);
        $staleDay = Carbon::now()->startOfDay()->subDays(5);
        $overtimeDay = Carbon::now()->startOfDay()->subDays(2);
        // Tag ohne (positive) Buchung mehr, aber mit altem offenen Eintrag → wird bereinigt
        $this->booking($user, $staleDay, -30);
        $this->booking($user, $overtimeDay, 90);
        UserOvertime::create([
            'user_id' => $user->id,
            'date' => $staleDay->toDateString(),
            'minutes' => 45,
            'remaining_minutes' => 45,
            'deadline' => $staleDay->copy()->addDays(30)->toDateString(),
            'status' => UserOvertime::STATUS_OPEN,
        ]);

        $this->service->recomputeForUser($user);

        $this->assertSame(
            [$overtimeDay->toDateString()],
            UserOvertime::where(
                'user_id',
                $user->id
            )->orderBy('date')->get()->map(fn (UserOvertime $e) => $e->date->toDateString())->all()
        );
    }
}
