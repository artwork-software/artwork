<?php

namespace Tests\Feature\Modules\WorkTime;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * „Tag neu buchen“: vergangene Tage nur auf ausdrücklichen Klick nach aktueller Rechnung buchen.
 */
final class WorkTimeRebookTest extends FeatureTestCase
{
    private function userWithDailyTarget(string $time = '08:00'): User
    {
        $user = User::factory()->create(['can_work_shifts' => true, 'work_time_balance' => 0]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => $time,
            'tuesday' => $time,
            'wednesday' => $time,
            'thursday' => $time,
            'friday' => $time,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function dailyBooking(User $user, string $day, int $worked, int $wanted): void
    {
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'name' => WorkTimeBookingRepository::dailyBookingName(Carbon::parse($day)),
            'booking_day' => $day,
            'booking_weekday' => Carbon::parse($day)->dayOfWeek,
            'wanted_working_hours' => $wanted,
            'worked_hours' => $worked,
            'work_time_balance_change' => $worked - $wanted,
        ]);
        $user->increment('work_time_balance', $worked - $wanted);
    }

    #[Test]
    public function a_never_booked_day_is_booked_on_request(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->actingAsAdmin(User::factory()->create());

        $this->post(route('users.worktimes.rebook', $user), ['dates' => ['2026-09-08']])->assertRedirect();

        $this->assertSame(-480, (int) $user->fresh()->work_time_balance);
        $this->assertSame(1, $user->workTimeBookings()->count());
    }

    #[Test]
    public function a_later_sick_note_is_booked_as_delta_and_rebooking_twice_is_idempotent(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->dailyBooking($user, '2026-09-08', 0, 480);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-09-08',
            'full_day' => true,
            'type' => 'NOT_AVAILABLE',
        ]);
        $this->actingAsAdmin(User::factory()->create());

        $this->post(route('users.worktimes.rebook', $user), ['dates' => ['2026-09-08']])->assertRedirect();
        $this->post(route('users.worktimes.rebook', $user), ['dates' => ['2026-09-08']])->assertRedirect();

        $this->assertSame(0, (int) $user->fresh()->work_time_balance); // krank: Ist = Soll
        $this->assertSame(1, $user->workTimeBookings()->count());
    }

    #[Test]
    public function today_and_future_days_are_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->actingAsAdmin(User::factory()->create());

        $this->post(route('users.worktimes.rebook', $user), ['dates' => ['2026-09-10']])
            ->assertSessionHasErrors('dates.0');

        $this->assertSame(0, $user->workTimeBookings()->count());
    }

    #[Test]
    public function rebooking_requires_the_manage_workers_permission(): void
    {
        $user = $this->userWithDailyTarget();
        $this->actingAs(User::factory()->create());

        $this->post(route('users.worktimes.rebook', $user), ['dates' => [now()->subDay()->toDateString()]])
            ->assertForbidden();
    }

    #[Test]
    public function a_legacy_correction_booked_on_the_approval_day_is_not_booked_twice(): void
    {
        // Bis 10/2026: Zeitänderung als Zeile "adjustment_…_<shift>" am Genehmigungstag. Die Abweichung des
        // Schichttags ist damit schon gebucht – Neu buchen darf sie nicht ein zweites Mal buchen.
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $shift = \Artwork\Modules\Shift\Models\Shift::factory()->create([
            'start_date' => '2026-09-08', 'end_date' => '2026-09-08', 'start' => '10:00:00', 'end' => '20:00:00',
        ]);
        $user->shifts()->attach($shift->id, [
            'shift_qualification_id' => \Artwork\Modules\Shift\Models\ShiftQualification::factory()->create()->id,
            'start_date' => '2026-09-08', 'end_date' => '2026-09-08', 'start_time' => '10:00', 'end_time' => '20:00',
        ]);
        $this->dailyBooking($user, '2026-09-08', 480, 480); // gebucht mit alter Zeit 10–18
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'name' => 'adjustment_work_time_change_request_' . $shift->id,
            'booking_day' => '2026-09-09',
            'booking_weekday' => 3,
            'wanted_working_hours' => 0,
            'worked_hours' => 0,
            'work_time_balance_change' => 120,
        ]);
        $user->increment('work_time_balance', 120);
        $this->actingAsAdmin(User::factory()->create());

        $response = $this->getJson(route('shift.user-info.worktimes', [
            'user' => $user->id, 'start' => '2026-09-08', 'end' => '2026-09-08',
        ]));
        $days = collect($response->json('workTimes'))->flatten(1)->keyBy('date');

        $this->assertFalse($days['2026-09-08']['needs_rebooking']);
    }

    #[Test]
    public function duplicate_legacy_daily_rows_do_not_cause_an_endless_rebook_hint(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->dailyBooking($user, '2026-09-08', 0, 480);
        $this->dailyBooking($user, '2026-09-08', 0, 480); // paralleler Nachtlauf (Altdaten)
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-09-08',
            'full_day' => true,
            'type' => 'NOT_AVAILABLE',
        ]);
        $this->actingAsAdmin(User::factory()->create());

        $this->post(route('users.worktimes.rebook', $user), ['dates' => ['2026-09-08']])->assertRedirect();

        $day = collect($this->getJson(route('shift.user-info.worktimes', [
            'user' => $user->id, 'start' => '2026-09-08', 'end' => '2026-09-08',
        ]))->json('workTimes'))->flatten(1)->first();
        $this->assertFalse($day['needs_rebooking']);
        $this->assertSame(-480, (int) $user->fresh()->work_time_balance); // −960 + 480 (erste Zeile 0)
    }

    #[Test]
    public function people_outside_the_shift_plan_cannot_be_rebooked(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $user->update(['can_work_shifts' => false]);
        $this->actingAsAdmin(User::factory()->create());

        $this->post(route('users.worktimes.rebook', $user), ['dates' => ['2026-09-08']])
            ->assertSessionHasErrors('dates');
        $this->assertSame(0, $user->workTimeBookings()->count());
    }

    #[Test]
    public function a_legacy_daily_row_with_inconsistent_balance_is_shown_consistently(): void
    {
        // Frühere Krank-Logik: worked 0, wanted 480, change 0 -> Tagessaldo und Summen müssen 0 zeigen
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        WorkTimeBooking::create([
            'user_id' => $user->id,
            'name' => WorkTimeBookingRepository::dailyBookingName(Carbon::parse('2026-09-08')),
            'booking_day' => '2026-09-08',
            'booking_weekday' => 2,
            'wanted_working_hours' => 480,
            'worked_hours' => 0,
            'work_time_balance_change' => 0,
        ]);
        $this->actingAsAdmin(User::factory()->create());

        $response = $this->getJson(route('shift.user-info.worktimes', [
            'user' => $user->id, 'start' => '2026-09-08', 'end' => '2026-09-08',
        ]));

        $response->assertJsonPath('totals.difference_minutes', 0);
    }
}
