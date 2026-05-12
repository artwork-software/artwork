<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class AppControllerTest extends FeatureTestCase
{
    #[Test]
    public function password_feedback_returns_integer_score_for_strong_password(): void
    {
        $response = $this->get('/password_feedback?password=' . urlencode('CorrectHorseBatteryStaple-9!'));

        $response->assertOk();
        $this->assertIsNumeric($response->getContent());
        $this->assertGreaterThanOrEqual(0, (int) $response->getContent());
        $this->assertLessThanOrEqual(4, (int) $response->getContent());
    }

    #[Test]
    public function password_feedback_returns_low_score_for_weak_password(): void
    {
        $response = $this->get('/password_feedback?password=password');

        $response->assertOk();
        $this->assertLessThanOrEqual(2, (int) $response->getContent());
    }

    #[Test]
    public function guest_is_redirected_to_login_when_setup_finished(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->setup_finished = true;
        $settings->save();

        $this->get('/')->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_is_redirected_to_setup_when_setup_not_finished(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->setup_finished = false;
        $settings->save();

        $this->get('/')->assertRedirect(route('setup'));
    }

    #[Test]
    public function setup_page_redirects_to_login_when_already_finished(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->setup_finished = true;
        $settings->save();

        $this->get(route('setup'))->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_toggle_hints(): void
    {
        $this->post(route('toggle.hints'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_toggle_hints(): void
    {
        $user = $this->actingAsAdmin();
        $user->update(['toggle_hints' => false]);

        $response = $this->post(route('toggle.hints'));

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->toggle_hints);
    }

    #[Test]
    public function authenticated_user_can_toggle_hints_back_to_false(): void
    {
        $user = $this->actingAsAdmin();
        $user->update(['toggle_hints' => true]);

        $this->post(route('toggle.hints'))->assertRedirect();
        $this->assertFalse($user->fresh()->toggle_hints);
    }

    #[Test]
    public function guest_cannot_toggle_calendar_settings_project_status(): void
    {
        $this->post(route('toggle.calendar_settings_project_status'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_toggle_calendar_settings_project_status(): void
    {
        $user = $this->actingAsAdmin();
        $user->calendar_settings()->update(['project_status' => false]);

        $this->post(route('toggle.calendar_settings_project_status'))->assertRedirect();
        $this->assertTrue((bool) $user->calendar_settings()->first()->project_status);
    }

    #[Test]
    public function admin_can_toggle_calendar_settings_options(): void
    {
        $user = $this->actingAsAdmin();
        $user->calendar_settings()->update(['options' => false]);

        $this->post(route('toggle.calendar_settings_options'))->assertRedirect();
        $this->assertTrue((bool) $user->calendar_settings()->first()->options);
    }

    #[Test]
    public function admin_can_toggle_calendar_settings_project_management(): void
    {
        $user = $this->actingAsAdmin();
        $user->calendar_settings()->update(['project_management' => false]);

        $this->post(route('toggle.calendar_settings_project_management'))->assertRedirect();
        $this->assertTrue((bool) $user->calendar_settings()->first()->project_management);
    }

    #[Test]
    public function admin_can_toggle_calendar_settings_repeating_events(): void
    {
        $user = $this->actingAsAdmin();
        $user->calendar_settings()->update(['repeating_events' => false]);

        $this->post(route('toggle.calendar_settings_repeating_events'))->assertRedirect();
        $this->assertTrue((bool) $user->calendar_settings()->first()->repeating_events);
    }

    #[Test]
    public function admin_can_toggle_calendar_settings_work_shifts(): void
    {
        $user = $this->actingAsAdmin();
        $user->calendar_settings()->update(['work_shifts' => false]);

        $this->post(route('toggle.calendar_settings_work_shifts'))->assertRedirect();
        $this->assertTrue((bool) $user->calendar_settings()->first()->work_shifts);
    }

    #[Test]
    public function toggle_use_project_period_validates_use_project_time_period(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(
            route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
            []
        );

        $response->assertSessionHasErrors('use_project_time_period');
    }

    #[Test]
    public function toggle_use_project_period_rejects_unknown_project_id_when_enabled(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(
            route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
            ['use_project_time_period' => true, 'project_id' => 999999]
        );

        $response->assertSessionHasErrors('project_id');
    }

    #[Test]
    public function toggle_use_project_period_succeeds_when_disabling(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(
            route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
            ['use_project_time_period' => false]
        );

        // Redirects to events route
        $response->assertRedirect();
    }
}
