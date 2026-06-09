<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Carbon\Carbon;
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
    public function paid_out_entries_are_not_recomputed(): void
    {
        $user = $this->userWithContract(period: 30);
        $day = Carbon::now()->startOfDay()->subDays(3);

        $entry = UserOvertime::create([
            'user_id' => $user->id,
            'date' => $day->toDateString(),
            'minutes' => 90,
            'remaining_minutes' => 90,
            'deadline' => $day->copy()->addDays(30)->toDateString(),
            'status' => UserOvertime::STATUS_PAID_OUT,
            'paid_out_at' => Carbon::now(),
        ]);
        $this->booking($user, $day, 90);

        $this->service->recomputeForUser($user);

        $entry->refresh();
        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $entry->status);
        $this->assertSame(1, UserOvertime::where('user_id', $user->id)->count());
    }

    #[Test]
    public function book_out_marks_payable_entry_as_paid_out(): void
    {
        $user = $this->userWithContract();
        $hr = User::factory()->create();
        $entry = UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'minutes' => 60,
            'remaining_minutes' => 60,
            'deadline' => Carbon::now()->subDay()->toDateString(),
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);

        $this->service->bookOut($entry, $hr->id, 'Paid with March payroll');

        $entry->refresh();
        $this->assertSame(UserOvertime::STATUS_PAID_OUT, $entry->status);
        $this->assertSame($hr->id, $entry->paid_out_by);
        $this->assertSame('Paid with March payroll', $entry->payout_reason);
        $this->assertNotNull($entry->paid_out_at);
    }

    #[Test]
    public function book_out_does_nothing_for_non_payable_entry(): void
    {
        $user = $this->userWithContract();
        $hr = User::factory()->create();
        $entry = UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'minutes' => 60,
            'remaining_minutes' => 60,
            'deadline' => Carbon::now()->addDays(10)->toDateString(),
            'status' => UserOvertime::STATUS_OPEN,
        ]);

        $this->service->bookOut($entry, $hr->id, 'should not apply');

        $entry->refresh();
        $this->assertSame(UserOvertime::STATUS_OPEN, $entry->status);
        $this->assertNull($entry->paid_out_by);
    }
}
