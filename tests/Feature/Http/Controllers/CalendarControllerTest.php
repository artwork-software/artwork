<?php

namespace Tests\Feature\Http\Controllers;

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
    public function admin_can_store_calendar_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('calendar-settings.store'), [
            'start' => '08:00',
            'end' => '20:00',
        ]);

        $response->assertRedirect();
    }
}
