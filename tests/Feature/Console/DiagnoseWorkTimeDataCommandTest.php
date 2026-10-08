<?php

namespace Tests\Feature\Console;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DiagnoseWorkTimeDataCommandTest extends TestCase
{
    #[Test]
    public function it_lists_duplicate_daily_bookings_and_balance_mismatches_without_changing_data(): void
    {
        $user = User::factory()->create(['work_time_balance' => -480]);
        foreach ([1, 2] as $ignored) {
            WorkTimeBooking::create([
                'user_id' => $user->id,
                'name' => 'daily_work_time_booking_2026-08-31',
                'booking_day' => '2026-08-31',
                'booking_weekday' => 1,
                'wanted_working_hours' => 480,
                'worked_hours' => 0,
                'work_time_balance_change' => -480,
            ]);
        }

        $this->artisan('artwork:work-time:diagnose', ['--limit' => 500])
            ->expectsOutputToContain('Doppelte Tagesbuchungen')
            ->assertSuccessful();

        $this->assertSame(2, $user->workTimeBookings()->count());
        $this->assertSame(-480, (int) $user->fresh()->work_time_balance);
    }
}
