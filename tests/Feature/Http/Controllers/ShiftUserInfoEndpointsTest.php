<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * DP-18 Info-Fenster: Zeitraum-Parameter und Fehler-JSON der shift-info-Endpunkte.
 */
final class ShiftUserInfoEndpointsTest extends FeatureTestCase
{
    private function settings(string $start, string $end): void
    {
        $settings = app(GeneralSettings::class);
        $settings->playing_time_window_start = $start;
        $settings->playing_time_window_end = $end;
        $settings->save();
    }

    #[Test]
    public function season_endpoint_falls_back_to_the_calendar_year_when_the_playing_time_window_is_missing(): void
    {
        $this->actingAsAdmin();
        $this->settings('', '');
        $user = User::factory()->create(['can_work_shifts' => true]);

        $response = $this->getJson(route('shift.user-info.season', ['user' => $user->id]));

        // Produktentscheidung: ohne Spielzeit gilt das Kalenderjahr – kein 422 mehr, dafür configured=false
        $response->assertOk()
            ->assertJsonPath('season.start', now()->startOfYear()->toDateString())
            ->assertJsonPath('season.end', now()->endOfYear()->toDateString())
            ->assertJsonPath('season.configured', false)
            ->assertJsonStructure(['kpis' => ['free_sundays_per_season', 'targets'], 'counted_until']);
    }

    #[Test]
    public function season_endpoint_returns_kpis_and_targets(): void
    {
        $this->actingAsAdmin();
        $this->settings('2025-08-01', '2026-07-31');
        $user = User::factory()->create(['can_work_shifts' => true]);

        $response = $this->getJson(route('shift.user-info.season', ['user' => $user->id]));

        $response->assertOk()
            ->assertJsonPath('season.start', '2025-08-01')
            ->assertJsonPath('season.end', '2026-07-31')
            ->assertJsonPath('season.configured', true)
            ->assertJsonStructure(['kpis' => ['free_sundays_per_season', 'targets'], 'counted_until']);
    }

    #[Test]
    public function worktimes_endpoint_honours_start_and_end_parameters(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['can_work_shifts' => true]);
        // Soll kommt ausschließlich aus dem Arbeitszeitmuster (Block 4): Mo–Fr 8 h
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

        $response = $this->getJson(route('shift.user-info.worktimes', [
            'user' => $user->id,
            'start' => '2026-06-01',
            'end' => '2026-06-30',
        ]));

        $response->assertOk()
            ->assertJsonPath('dateRange.start', '2026-06-01')
            ->assertJsonPath('dateRange.end', '2026-06-30')
            ->assertJsonStructure(['workTimes', 'totals' => ['worked', 'wanted', 'difference_minutes', 'difference_signed']]);

        $days = collect($response->json('workTimes'))->flatten(1)->keyBy('date');
        $this->assertCount(30, $days);
        $this->assertSame(480, $days['2026-06-01']['daily_target_minutes']); // Montag laut Muster
        $this->assertSame(0, $days['2026-06-06']['daily_target_minutes']);   // Samstag (Muster ohne Zeit)
        $this->assertFalse($days['2026-06-01']['target_unknown']);
        $this->assertFalse($response->json('totals.target_unknown'));
        $this->assertArrayHasKey('reduction_reason', $days['2026-06-01']);
    }

    #[Test]
    public function worktimes_endpoint_falls_back_to_the_current_month_on_invalid_dates(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['can_work_shifts' => true]);

        $response = $this->getJson(route('shift.user-info.worktimes', [
            'user' => $user->id,
            'start' => 'NaN-NaN-NaN',
            'end' => 'nope',
        ]));

        $response->assertOk()
            ->assertJsonPath('dateRange.start', now()->startOfMonth()->toDateString())
            ->assertJsonPath('dateRange.end', now()->endOfMonth()->toDateString());
    }

    #[Test]
    public function vacation_endpoint_reports_the_calendar_year_including_planned_days(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['can_work_shifts' => true]);

        $response = $this->getJson(route('shift.user-info.vacation', ['user' => $user->id]));

        $response->assertOk()
            ->assertJsonPath('year', now()->year)
            ->assertJsonPath('includes_planned', true)
            ->assertJsonStructure(['period' => ['start', 'end'], 'entitlement', 'granted', 'remaining', 'vacations']);
    }

    #[Test]
    public function worktimes_of_other_users_require_the_hour_account_permission(): void
    {
        $viewer = $this->actingAsUserWith('can view shift user kpis');
        $other = User::factory()->create(['can_work_shifts' => true]);

        $this->getJson(route('shift.user-info.worktimes', ['user' => $other->id]))->assertForbidden();
        $this->getJson(route('shift.user-info.worktimes', ['user' => $viewer->id]))->assertOk();
    }
}
