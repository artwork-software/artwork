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
    public function inactive_rule_creates_no_entries(): void
    {
        $user = $this->userWithContract(active: false, period: 30);
        $this->booking($user, Carbon::now()->startOfDay(), 120);

        $this->service->recomputeForUser($user);

        $this->assertSame(0, UserOvertime::where('user_id', $user->id)->count());
    }

    #[Test]
    public function paid_out_minutes_survive_a_recompute(): void
    {
        $user = $this->userWithContract(period: 30);
        $day = Carbon::now()->startOfDay()->subDays(3);

        $entry = UserOvertime::create([
            'user_id' => $user->id,
            'date' => $day->toDateString(),
            'minutes' => 90,
            'remaining_minutes' => 0,
            'paid_out_minutes' => 90,
            'deadline' => $day->copy()->addDays(30)->toDateString(),
            'status' => UserOvertime::STATUS_PAID_OUT,
            'paid_out_at' => Carbon::now(),
        ]);
        $this->booking($user, $day, 90);

        $this->service->recomputeForUser($user);

        $entry->refresh();
        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $entry->status);
        $this->assertSame(0, $entry->remaining_minutes);
        $this->assertSame(90, $entry->paid_out_minutes);
        $this->assertSame(1, UserOvertime::where('user_id', $user->id)->count());
    }

    #[Test]
    public function pay_out_consumes_payable_entries_fifo_and_reduces_balance(): void
    {
        $user = $this->userWithContract();
        $hr = User::factory()->create();
        $first = UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subDays(20)->toDateString(),
            'minutes' => 60,
            'remaining_minutes' => 60,
            'deadline' => Carbon::now()->subDays(10)->toDateString(),
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);
        $second = UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subDays(15)->toDateString(),
            'minutes' => 90,
            'remaining_minutes' => 90,
            'deadline' => Carbon::now()->subDays(5)->toDateString(),
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);
        $balanceBefore = (int) $user->work_time_balance;

        $this->service->payOut($user, 100, $hr->id, 'Paid with March payroll');

        $first->refresh();
        $second->refresh();
        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $first->status);
        $this->assertSame(0, $first->remaining_minutes);
        $this->assertSame(60, $first->paid_out_minutes);
        $this->assertSame($hr->id, $first->paid_out_by);
        $this->assertSame(UserOvertime::STATUS_PAYABLE, $second->status);
        $this->assertSame(50, $second->remaining_minutes);
        $this->assertSame(40, $second->paid_out_minutes);

        $this->assertSame(1, OvertimePayout::where('user_id', $user->id)->count());
        $payout = OvertimePayout::where('user_id', $user->id)->first();
        $this->assertSame(100, $payout->minutes);
        $this->assertSame($hr->id, $payout->created_by);
        $this->assertSame('Paid with March payroll', $payout->comment);

        $this->assertSame($balanceBefore - 100, (int) $user->fresh()->work_time_balance);
    }

    #[Test]
    public function pay_out_rejects_amount_exceeding_payable_total(): void
    {
        $user = $this->userWithContract();
        $hr = User::factory()->create();
        UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subDays(20)->toDateString(),
            'minutes' => 60,
            'remaining_minutes' => 60,
            'deadline' => Carbon::now()->subDays(10)->toDateString(),
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->payOut($user, 61, $hr->id, null);
    }

    #[Test]
    public function pay_out_does_not_touch_open_entries(): void
    {
        $user = $this->userWithContract();
        $hr = User::factory()->create();
        $payable = UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subDays(20)->toDateString(),
            'minutes' => 60,
            'remaining_minutes' => 60,
            'deadline' => Carbon::now()->subDays(10)->toDateString(),
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);
        $open = UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'minutes' => 120,
            'remaining_minutes' => 120,
            'deadline' => Carbon::now()->addDays(10)->toDateString(),
            'status' => UserOvertime::STATUS_OPEN,
        ]);

        $this->service->payOut($user, 60, $hr->id, null);

        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $payable->fresh()->status);
        $open->refresh();
        $this->assertSame(UserOvertime::STATUS_OPEN, $open->status);
        $this->assertSame(120, $open->remaining_minutes);
        $this->assertSame(0, $open->paid_out_minutes);
    }

    #[Test]
    public function an_inactive_rule_keeps_existing_open_and_compensated_entries(): void
    {
        // Zuweisung heute mit overtime_rule_active=false (z. B. nach Vertragswechsel): der Replay beurteilt
        // keinen Tag — vorhandene Einträge dürfen dann nicht als "veraltet" gelöscht werden.
        $user = $this->userWithContract(active: false, period: 30);
        $openDay = Carbon::now()->startOfDay()->subDays(5);
        $compensatedDay = Carbon::now()->startOfDay()->subDays(10);
        $this->booking($user, $openDay, 120);
        $this->booking($user, $compensatedDay, 60);
        $open = UserOvertime::create([
            'user_id' => $user->id,
            'date' => $openDay->toDateString(),
            'minutes' => 120,
            'remaining_minutes' => 120,
            'deadline' => $openDay->copy()->addDays(30)->toDateString(),
            'status' => UserOvertime::STATUS_OPEN,
        ]);
        $compensated = UserOvertime::create([
            'user_id' => $user->id,
            'date' => $compensatedDay->toDateString(),
            'minutes' => 60,
            'remaining_minutes' => 0,
            'deadline' => $compensatedDay->copy()->addDays(30)->toDateString(),
            'status' => UserOvertime::STATUS_COMPENSATED,
        ]);

        $this->service->recomputeForUser($user);

        $this->assertSame(2, UserOvertime::where('user_id', $user->id)->count());
        $this->assertSame(UserOvertime::STATUS_OPEN, $open->fresh()->status);
        $this->assertSame(120, $open->fresh()->remaining_minutes);
        $this->assertSame(UserOvertime::STATUS_COMPENSATED, $compensated->fresh()->status);
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
            UserOvertime::where('user_id', $user->id)->orderBy('date')->get()->map(fn (UserOvertime $e) => $e->date->toDateString())->all()
        );
    }
}
