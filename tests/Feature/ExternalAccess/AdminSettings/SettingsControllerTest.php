<?php

namespace Tests\Feature\ExternalAccess\AdminSettings;

use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class SettingsControllerTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name_override' => 'Acme',
            'default_crm_access_months' => 12,
            'default_tab_access_days' => 90,
            'login_token_lifetime_minutes' => 15,
            'session_idle_timeout_minutes' => 120,
            'session_absolute_lifetime_minutes' => 480,
            'rate_limit_request_link_per_email_per_hour' => 3,
            'rate_limit_request_link_per_ip_per_hour' => 10,
        ], $overrides);
    }

    #[Test]
    public function non_admin_cannot_access_settings_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('settings.external-access.index'))->assertForbidden();
    }

    #[Test]
    public function admin_can_access_settings_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('settings.external-access.index'))->assertOk();
    }

    #[Test]
    public function admin_can_update_settings(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('settings.external-access.update'), $this->validPayload([
            'default_crm_access_months' => 24,
        ]))->assertStatus(302);

        $this->assertSame(24, app(ExternalAccessSettings::class)->default_crm_access_months);
    }

    #[Test]
    public function non_admin_cannot_update_settings(): void
    {
        $this->actingAs(User::factory()->create());

        $this->patch(route('settings.external-access.update'), $this->validPayload())->assertForbidden();
    }

    #[Test]
    public function idle_timeout_above_absolute_lifetime_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('settings.external-access.update'), $this->validPayload([
            'session_idle_timeout_minutes' => 480,
            'session_absolute_lifetime_minutes' => 120,
        ]))->assertSessionHasErrors('session_idle_timeout_minutes');
    }

    #[Test]
    public function login_token_lifetime_outside_hard_limits_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('settings.external-access.update'), $this->validPayload([
            'login_token_lifetime_minutes' => 120,
        ]))->assertSessionHasErrors('login_token_lifetime_minutes');
    }

    #[Test]
    public function audit_log_entry_is_created_on_update(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('settings.external-access.update'), $this->validPayload());

        $this->assertTrue(
            Activity::query()->where('log_name', 'external_access_settings')
                ->where('description', 'settings_updated')->exists()
        );
    }
}
