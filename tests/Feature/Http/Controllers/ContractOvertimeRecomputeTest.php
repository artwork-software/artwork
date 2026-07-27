<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ContractOvertimeRecomputeTest extends FeatureTestCase
{
    private function userWithOvertimeBooking(): User
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $user->givePermissionTo(Permission::findOrCreate(PermissionEnum::MA_MANAGER->value, 'web'));

        $day = Carbon::now()->startOfDay()->subDays(3);
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'name' => 'test_booking',
            'booking_day' => $day->toDateString(),
            'booking_weekday' => $day->dayOfWeek,
            'wanted_working_hours' => 0,
            'worked_hours' => 2,
            'work_time_balance_change' => 120,
        ]);

        return $user;
    }

    #[Test]
    public function activating_the_overtime_rule_on_the_contract_recomputes_overtime(): void
    {
        $user = $this->userWithOvertimeBooking();
        $this->actingAs($user);

        // No overtime entries before the contract change.
        $this->assertSame(0, UserOvertime::where('user_id', $user->id)->count());

        $response = $this->patch(route('user-contract-settings.update-user', ['user' => $user->id]), [
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 30,
        ]);

        $response->assertStatus(302);

        // The recompute triggered by the contract change materialised the booking as an overtime entry.
        $this->assertDatabaseHas('user_overtimes', [
            'user_id' => $user->id,
            'minutes' => 120,
            'remaining_minutes' => 120,
            'status' => UserOvertime::STATUS_OPEN,
        ]);
    }

    #[Test]
    public function leaving_the_overtime_rule_inactive_creates_no_entries(): void
    {
        $user = $this->userWithOvertimeBooking();
        $this->actingAs($user);

        $response = $this->patch(route('user-contract-settings.update-user', ['user' => $user->id]), [
            'overtime_rule_active' => false,
            'overtime_compensation_period' => 30,
        ]);

        $response->assertStatus(302);
        $this->assertSame(0, UserOvertime::where('user_id', $user->id)->count());
    }
}
