<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class OvertimePayoutTest extends FeatureTestCase
{
    /**
     * Person mit aktiver Überstundenregel (Frist 5 Tage) und einem Plustag vor 20 Tagen → auszahlbar.
     * Einträge entstehen aus Buchungen (Kontoprinzip), Zeitkonto passend zur Buchung.
     */
    private function userWithPayableEntry(int $minutes = 60): User
    {
        $user = User::factory()->create(['work_time_balance' => $minutes]);
        UserContractAssign::factory()->create([
            'user_id' => $user->id,
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 5,
        ]);
        $day = Carbon::now()->subDays(20)->startOfDay();
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'name' => 'manual_booking',
            'booking_day' => $day->toDateString(),
            'booking_weekday' => $day->dayOfWeek,
            'wanted_working_hours' => 0,
            'worked_hours' => $minutes,
            'work_time_balance_change' => $minutes,
        ]);
        app(OvertimeService::class)->recomputeForUser($user);

        return $user->fresh();
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
        $this->assertSame(0, (int) $user->fresh()->work_time_balance);
        $response->assertJsonPath('balance_minutes', 0)
            ->assertJsonPath('payable_now_minutes', 0)
            ->assertJsonPath('account_difference_minutes', 0);
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
