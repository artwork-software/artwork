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

    #[Test]
    public function it_counts_all_findings_and_reports_overwritten_future_and_unknown_bookings(): void
    {
        $user = User::factory()->create(['work_time_balance' => 0]);
        $row = static fn (array $attributes): WorkTimeBooking => WorkTimeBooking::create(array_merge([
            'user_id' => $user->id,
            'booking_weekday' => 1,
            'wanted_working_hours' => 0,
            'worked_hours' => 0,
            'work_time_balance_change' => 0,
        ], $attributes));
        // Drei Tage mit doppelter Tageszeile – mit --limit=1 trotzdem vollständig gezählt
        foreach (['2026-08-03', '2026-08-04', '2026-08-05'] as $day) {
            $row(['name' => 'daily_work_time_booking_' . $day, 'booking_day' => $day]);
            $row(['name' => 'daily_work_time_booking_' . $day, 'booking_day' => $day]);
        }
        $row([
            'name' => 'daily_work_time_booking_2026-08-10',
            'booking_day' => '2026-08-10',
            'booker_id' => $user->id,
            'comment' => 'Manuell korrigiert',
        ]);
        $row(['name' => 'daily_work_time_booking_2026-08-12', 'booking_day' => '2026-08-11']);
        $row(['name' => 'manual_booking', 'booking_day' => now()->addDays(5)->toDateString()]);
        $row(['name' => 'irgendwas', 'booking_day' => '2026-08-13']);

        $this->artisan('artwork:work-time:diagnose', ['--limit' => 1])
            ->expectsOutputToContain('Doppelte Tagesbuchungen am selben Tag')
            ->expectsOutputToContain('weitere (--limit erhöhen)')
            ->expectsOutputToContain('Tageszeilen mit booker_id oder Kommentar')
            ->expectsOutputToContain('anderes Datum trägt als booking_day')
            ->expectsOutputToContain('Buchungen in der Zukunft')
            ->expectsOutputToContain('irgendwas')
            ->assertSuccessful();

        $this->assertSame(10, $user->workTimeBookings()->count());
        $this->assertSame(0, (int) $user->fresh()->work_time_balance);
    }
}
