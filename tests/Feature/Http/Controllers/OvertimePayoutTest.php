<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class OvertimePayoutTest extends FeatureTestCase
{
    private function userWithPayableEntry(int $minutes = 60): User
    {
        $user = User::factory()->create();

        UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subDays(20)->toDateString(),
            'minutes' => $minutes,
            'remaining_minutes' => $minutes,
            'deadline' => Carbon::now()->subDay()->toDateString(),
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);

        return $user;
    }

    #[Test]
    public function hr_can_pay_out_payable_overtime(): void
    {
        $hr = $this->actingAsUserWith('can pay out overtime');
        $user = $this->userWithPayableEntry(60);

        $response = $this->postJson(route('user.overtime.payout', ['user' => $user->id]), [
            'minutes' => 60,
            'comment' => 'Paid out with payroll',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('user_overtimes', [
            'user_id' => $user->id,
            'status' => UserOvertime::STATUS_PAID_OUT,
            'remaining_minutes' => 0,
            'paid_out_minutes' => 60,
            'paid_out_by' => $hr->id,
        ]);
        $this->assertDatabaseHas('overtime_payouts', [
            'user_id' => $user->id,
            'minutes' => 60,
            'created_by' => $hr->id,
            'comment' => 'Paid out with payroll',
        ]);
        $this->assertSame(-60, (int) $user->fresh()->work_time_balance);
    }

    #[Test]
    public function payout_exceeding_payable_total_is_rejected(): void
    {
        $this->actingAsUserWith('can pay out overtime');
        $user = $this->userWithPayableEntry(60);

        $response = $this->postJson(route('user.overtime.payout', ['user' => $user->id]), [
            'minutes' => 120,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('overtime_payouts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('user_overtimes', [
            'user_id' => $user->id,
            'status' => UserOvertime::STATUS_PAYABLE,
            'remaining_minutes' => 60,
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_pay_out(): void
    {
        $this->actingAs(User::factory()->create());
        $user = $this->userWithPayableEntry(60);

        $response = $this->postJson(route('user.overtime.payout', ['user' => $user->id]), [
            'minutes' => 60,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('user_overtimes', [
            'user_id' => $user->id,
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);
    }
}
