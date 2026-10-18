<?php

namespace Tests\Feature\Modules\WorkTime;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Arbeitszeiten-Tab und Info-Modal im Dienstplan zeigen vor Beginn des Zeitkontos (erste Tagesbuchung) dasselbe
 * wie der Arbeitszeitübersicht-Export: kein Soll, kein Saldo, kein soll-neutrales Ist – nur Gearbeitetes.
 */
final class WorkTimeAccountStartDisplayTest extends FeatureTestCase
{
    private function userWithDailyTarget(): User
    {
        $user = User::factory()->create(['can_work_shifts' => true, 'work_time_balance' => 0]);
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'monday' => '08:00',
            'tuesday' => '08:00',
            'wednesday' => '08:00',
            'thursday' => '08:00',
            'friday' => '08:00',
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Di 01.09.: 4 h Schicht, Mi 02.09.: ganztägig krank – beides vor Beginn des Zeitkontos
        $shift = Shift::factory()->create([
            'start_date' => '2026-09-01', 'end_date' => '2026-09-01', 'start' => '10:00:00', 'end' => '14:00:00',
            'break_minutes' => 0,
        ]);
        $user->shifts()->attach($shift->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'start_date' => '2026-09-01', 'end_date' => '2026-09-01', 'start_time' => '10:00', 'end_time' => '14:00',
        ]);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2026-09-02',
            'full_day' => true,
            'type' => 'NOT_AVAILABLE',
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
    }

    /**
     * @return array{days: array<string, array<string, mixed>>, totals: array<string, mixed>}
     */
    private function modalData(User $user, string $start, string $end): array
    {
        $response = $this->getJson(route('shift.user-info.worktimes', [
            'user' => $user->id, 'start' => $start, 'end' => $end,
        ]))->assertOk();

        return [
            'days' => collect($response->json('workTimes'))->flatten(1)->keyBy('date')->all(),
            'totals' => $response->json('totals'),
        ];
    }

    /**
     * @return array{days: array<string, array<string, mixed>>, totals: array<string, mixed>}
     */
    private function tabData(User $user, string $start, string $end): array
    {
        $response = $this->get(route('user.edit.worktimes', ['user' => $user->id, 'start' => $start, 'end' => $end]))
            ->assertOk();
        $props = $response->viewData('page')['props'];

        return [
            'days' => collect($props['workTimes'])->flatten(1)->keyBy('date')->all(),
            'totals' => $props['totals'],
        ];
    }

    /**
     * @param array{days: array<string, array<string, mixed>>, totals: array<string, mixed>} $data
     */
    private function assertAccountStartedOnFriday(array $data): void
    {
        $days = $data['days'];

        // Di vor Kontobeginn: gearbeitete Schicht sichtbar, kein Soll, kein Saldo, kein Neu-buchen
        $tuesday = $days['2026-09-01'];
        $this->assertTrue($tuesday['before_account_start']);
        $this->assertSame(240, $tuesday['worked_hours']);
        $this->assertSame(0, $tuesday['daily_target_minutes']);
        $this->assertSame('–', $tuesday['daily_target_hours']);
        $this->assertNull($tuesday['wantedHours']);
        $this->assertNull($tuesday['work_time_balance_change']);
        $this->assertNull($tuesday['work_time_balance_change_formatted']);
        $this->assertFalse($tuesday['target_unknown']);
        $this->assertFalse($tuesday['needs_rebooking']);

        // Mi krank vor Kontobeginn: kein soll-neutrales Ist
        $this->assertSame(0, $days['2026-09-02']['worked_hours']);
        $this->assertNull($days['2026-09-02']['work_time_balance_change']);

        // Ab Kontobeginn unverändert: gebuchter Fr, nicht gebuchter Mo mit Soll und Minus-Saldo
        $this->assertFalse($days['2026-09-04']['before_account_start']);
        $this->assertSame(480, $days['2026-09-04']['daily_target_minutes']);
        $this->assertSame(0, $days['2026-09-04']['work_time_balance_change']);
        $this->assertFalse($days['2026-09-07']['before_account_start']);
        $this->assertSame(480, $days['2026-09-07']['wantedHours']);
        $this->assertSame(-480, $days['2026-09-07']['work_time_balance_change']);
        $this->assertTrue($days['2026-09-07']['needs_rebooking']);

        // Summen: Gearbeitet über alles, Soll/Saldo nur ab Kontobeginn (Fr 8 h + Mo 8 h + Di 8 h)
        $totals = $data['totals'];
        $this->assertSame(240 + 480, $totals['worked_minutes']);
        $this->assertSame(3 * 480, $totals['wanted_minutes']);
        $this->assertSame(480 - 3 * 480, $totals['difference_minutes']);
        $this->assertFalse($totals['target_unknown']);
        $this->assertFalse($totals['account_not_started']);
        $this->assertSame(4, $totals['days_before_account_start']); // Mo 31.08. – Do 03.09.
    }

    #[Test]
    public function the_info_modal_shows_no_target_and_no_balance_before_the_account_start(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->dailyBooking($user, '2026-09-04', 480, 480); // Zeitkonto beginnt am Fr 04.09.
        $this->actingAsAdmin(User::factory()->create());

        $this->assertAccountStartedOnFriday($this->modalData($user, '2026-08-31', '2026-09-08'));
    }

    #[Test]
    public function the_work_times_tab_shows_no_target_and_no_balance_before_the_account_start(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->dailyBooking($user, '2026-09-04', 480, 480);
        $this->actingAsAdmin(User::factory()->create());

        $this->assertAccountStartedOnFriday($this->tabData($user, '2026-08-31', '2026-09-08'));
    }

    #[Test]
    public function people_without_any_daily_booking_get_neither_target_nor_balance(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->actingAsAdmin(User::factory()->create());

        $views = [
            $this->modalData($user, '2026-09-01', '2026-09-15'),
            $this->tabData($user, '2026-09-01', '2026-09-15'),
        ];
        foreach ($views as $data) {
            foreach ($data['days'] as $day) {
                $this->assertTrue($day['before_account_start'], $day['date']);
                $this->assertNull($day['wantedHours'], $day['date']);
                $this->assertNull($day['work_time_balance_change'], $day['date']);
            }
            $this->assertSame(240, $data['days']['2026-09-01']['worked_hours']);
            $this->assertSame(240, $data['totals']['worked_minutes']);
            $this->assertNull($data['totals']['wanted_minutes']);
            $this->assertNull($data['totals']['wanted']);
            $this->assertNull($data['totals']['difference_minutes']);
            $this->assertNull($data['totals']['difference_signed']);
            $this->assertFalse($data['totals']['target_unknown']); // kein „Arbeitszeitmuster fehlt“
            $this->assertTrue($data['totals']['account_not_started']);
            $this->assertSame(15, $data['totals']['days_before_account_start']);
            $this->assertSame(0, $data['totals']['rebook_days']);
        }
    }

    #[Test]
    public function a_range_entirely_after_the_account_start_is_unchanged(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00'));
        $user = $this->userWithDailyTarget();
        $this->dailyBooking($user, '2026-08-03', 480, 480);
        $this->actingAsAdmin(User::factory()->create());

        $data = $this->modalData($user, '2026-09-01', '2026-09-02');

        // Di 4 h Schicht bei 8 h Soll, Mi krank (Ist = Soll) – beide nicht gebucht, Live-Rechnung
        $this->assertFalse($data['days']['2026-09-01']['before_account_start']);
        $this->assertSame(-240, $data['days']['2026-09-01']['work_time_balance_change']);
        $this->assertSame(480, $data['days']['2026-09-02']['worked_hours']);
        $this->assertSame(0, $data['days']['2026-09-02']['work_time_balance_change']);
        $this->assertSame(960, $data['totals']['wanted_minutes']);
        $this->assertSame(-240, $data['totals']['difference_minutes']);
        $this->assertSame(0, $data['totals']['days_before_account_start']);
    }
}
