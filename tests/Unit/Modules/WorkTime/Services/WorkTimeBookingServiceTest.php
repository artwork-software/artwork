<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkTimeBookingServiceTest extends TestCase
{
    private WorkTimeBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkTimeBookingService::class);
    }

    #[Test]
    public function refresh_work_time_activations_runs_without_error(): void
    {
        // Ensure the method runs without throwing when there is nothing to update.
        $this->service->refreshWorkTimeActivations();

        $this->assertTrue(true);
    }

    #[Test]
    public function refresh_work_time_activations_activates_currently_valid_entries(): void
    {
        $user = User::factory()->create();
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->refreshWorkTimeActivations();

        $this->assertDatabaseHas('user_work_times', [
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function refresh_work_time_activations_deactivates_expired_entries(): void
    {
        $user = User::factory()->create();
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'valid_from' => now()->subYears(2),
            'valid_until' => now()->subYear(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->refreshWorkTimeActivations();

        $this->assertDatabaseHas('user_work_times', [
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }

    /**
     * DP-19: Sonntagsbedingte Ersatzruhe (for_holiday = false) darf das Tagessoll
     * nicht reduzieren — nur feiertagsbedingte Ausgleichstage tun das.
     */
    #[Test]
    public function sunday_based_compensation_day_off_does_not_reduce_the_daily_target(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00')); // Dienstag, kein Feiertag

        $user = $this->workShiftUserWithDailyTarget('08:00');
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 1.0,
            'deadline' => '2026-08-31',
            'granted_date' => '2026-07-21',
            'granted_at' => now(),
            'reason' => 'Ersatzruhe für Sonntagsarbeit',
            'for_holiday' => false,
        ]);

        $this->service->calculateDailyWorkingHours();

        $this->assertDatabaseHas('work_time_bookings', [
            'user_id' => $user->id,
            'name' => 'daily_work_time_booking_2026-07-21',
            'wanted_working_hours' => 480,
        ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function holiday_based_compensation_day_off_reduces_the_daily_target(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00')); // Dienstag, kein Feiertag

        $user = $this->workShiftUserWithDailyTarget('08:00');
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 1.0,
            'deadline' => '2026-08-31',
            'granted_date' => '2026-07-21',
            'granted_at' => now(),
            'reason' => 'Ausgleichstag für Feiertagsarbeit',
            'for_holiday' => true,
        ]);

        $this->service->calculateDailyWorkingHours();

        // Ohne Arbeitshistorie fällt der Dreimonatsdurchschnitt auf das Tagessoll
        // zurück -> volle Reduktion auf 0.
        $this->assertDatabaseHas('work_time_bookings', [
            'user_id' => $user->id,
            'name' => 'daily_work_time_booking_2026-07-21',
            'wanted_working_hours' => 0,
        ]);

        Carbon::setTestNow();
    }

    /**
     * Produktentscheidung 2026-07-29: Sondertage senken das Soll auch dann,
     * wenn gearbeitet wurde — die Arbeit erzeugt Plus-Stunden, die ein
     * regulärer Ausgleichstag (volles Soll) später abbaut.
     */
    #[Test]
    public function working_on_a_holiday_still_zeroes_the_daily_target(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00')); // Dienstag

        $user = $this->workShiftUserWithDailyTarget('08:00');
        Holiday::create([
            'name' => 'Testfeiertag',
            'date' => '2026-07-21',
            'yearly' => false,
            'from_api' => false,
        ]);
        $user->individualTimes()->create([
            'title' => 'Feiertagsdienst',
            'start_date' => '2026-07-21',
            'end_date' => '2026-07-21',
            'full_day' => true,
            'working_time_minutes' => 480,
        ]);

        $this->service->calculateDailyWorkingHours();

        $this->assertDatabaseHas('work_time_bookings', [
            'user_id' => $user->id,
            'name' => 'daily_work_time_booking_2026-07-21',
            'wanted_working_hours' => 0,
            'worked_hours' => 480,
            'work_time_balance_change' => 480,
        ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function holiday_based_compensation_day_off_reduces_the_target_even_when_work_was_performed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00')); // Dienstag, kein Feiertag

        $user = $this->workShiftUserWithDailyTarget('08:00');
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 1.0,
            'deadline' => '2026-08-31',
            'granted_date' => '2026-07-21',
            'granted_at' => now(),
            'reason' => 'Ausgleichstag für Feiertagsarbeit',
            'for_holiday' => true,
        ]);
        $user->individualTimes()->create([
            'title' => 'Kurzfristiger Einsatz',
            'start_date' => '2026-07-21',
            'end_date' => '2026-07-21',
            'full_day' => true,
            'working_time_minutes' => 120,
        ]);

        $this->service->calculateDailyWorkingHours();

        // Reduktion gilt trotz Arbeit; die 120 geleisteten Minuten werden Plus.
        $this->assertDatabaseHas('work_time_bookings', [
            'user_id' => $user->id,
            'name' => 'daily_work_time_booking_2026-07-21',
            'wanted_working_hours' => 0,
            'worked_hours' => 120,
            'work_time_balance_change' => 120,
        ]);

        Carbon::setTestNow();
    }

    private function workShiftUserWithDailyTarget(string $tuesdayTime): User
    {
        $user = User::factory()->create(['can_work_shifts' => true]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'tuesday' => $tuesdayTime,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
