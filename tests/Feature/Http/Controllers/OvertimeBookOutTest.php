<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class OvertimeBookOutTest extends FeatureTestCase
{
    private function payableEntry(): UserOvertime
    {
        $user = User::factory()->create();

        return UserOvertime::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'minutes' => 60,
            'remaining_minutes' => 60,
            'deadline' => Carbon::now()->subDay()->toDateString(),
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);
    }

    #[Test]
    public function hr_can_book_out_a_payable_overtime_entry(): void
    {
        $hr = $this->actingAsUserWith('can manage workers');
        $entry = $this->payableEntry();

        $response = $this->post(route('overtime.book-out', ['userOvertime' => $entry->id]), [
            'reason' => 'Paid out with payroll',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('user_overtimes', [
            'id' => $entry->id,
            'status' => UserOvertime::STATUS_PAID_OUT,
            'paid_out_by' => $hr->id,
            'payout_reason' => 'Paid out with payroll',
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_book_out(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->payableEntry();

        $response = $this->post(route('overtime.book-out', ['userOvertime' => $entry->id]), [
            'reason' => 'nope',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('user_overtimes', [
            'id' => $entry->id,
            'status' => UserOvertime::STATUS_PAYABLE,
        ]);
    }
}
