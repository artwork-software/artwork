<?php

namespace Tests\Feature\Http\Controllers;

use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CalendarControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_calendar_filter_definitions(): void
    {
        $this->getJson(route('calendar.filters'))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_get_calendar_filter_definitions(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('calendar.filters'));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    #[Test]
    public function guest_cannot_access_calendar_settings(): void
    {
        $this->get(route('calendar.settings'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_calendar_settings_page(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('calendar.settings'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Settings/Calendar/Index')
                ->has('calendarSettings')
        );
    }

    #[Test]
    public function regular_user_cannot_view_calendar_settings_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('calendar.settings'))->assertForbidden();
    }

    #[Test]
    public function admin_can_store_calendar_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('calendar-settings.store'), [
            'start' => '08:00',
            'end' => '20:00',
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function regular_user_cannot_change_global_calendar_settings(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('calendar-settings.store'), [
            'start' => '08:00',
            'end' => '20:00',
            'day_remarks_enabled' => true,
            'day_remarks_mandatory' => true,
        ])->assertForbidden();
    }

    #[Test]
    public function admin_can_disable_project_creation_from_events(): void
    {
        $this->actingAsAdmin();

        $this->post(route('calendar-settings.store'), [
            'start' => '08:00',
            'end' => '20:00',
            'project_creation_from_events_enabled' => false,
        ])->assertRedirect();

        $this->assertFalse(app(GeneralCalendarSettings::class)->project_creation_from_events_enabled);
        $this->get(route('calendar.settings'))->assertInertia(fn (AssertableInertia $page) =>
            $page->where('project_creation_from_events_enabled', false)
                ->where('calendarSettings.project_creation_from_events_enabled', false)
        );
    }

    #[Test]
    public function project_creation_from_events_stays_enabled_when_the_field_is_not_sent(): void
    {
        $this->actingAsAdmin();

        $this->post(route('calendar-settings.store'), [
            'start' => '08:00',
            'end' => '20:00',
        ])->assertRedirect();

        $this->assertTrue(app(GeneralCalendarSettings::class)->project_creation_from_events_enabled);
    }
}
