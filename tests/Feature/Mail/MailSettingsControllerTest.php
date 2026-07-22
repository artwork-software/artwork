<?php

namespace Tests\Feature\Mail;

use Artwork\Modules\Mail\Providers\MailSettingsServiceProvider;
use Artwork\Modules\Mail\Settings\MailSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

final class MailSettingsControllerTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer@example.com',
            'password' => 'super-secret',
            'from_address' => 'from@example.com',
            'from_name' => 'Artwork',
        ], $overrides);
    }

    #[Test]
    public function non_admin_cannot_access_mail_settings_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tool.mail'))->assertForbidden();
    }

    #[Test]
    public function admin_can_access_mail_settings_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tool.mail'))->assertOk();
    }

    #[Test]
    public function admin_can_update_mail_settings_including_password(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload())->assertStatus(302);

        $settings = app(MailSettings::class);
        $this->assertSame('smtp.example.com', $settings->host);
        $this->assertSame(587, $settings->port);
        $this->assertSame('tls', $settings->encryption);
        $this->assertSame('from@example.com', $settings->from_address);
        $this->assertSame('Artwork', $settings->from_name);
        // Password is persisted (stored encrypted, decrypted on read).
        $this->assertSame('super-secret', $settings->password);
    }

    #[Test]
    public function blank_password_keeps_the_previously_stored_password(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload(['password' => 'first-pw']));
        $this->patch(route('tool.mail.update'), $this->validPayload(['password' => '']));

        $this->assertSame('first-pw', app(MailSettings::class)->password);
    }

    #[Test]
    public function clear_password_flag_removes_the_stored_password(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload(['password' => 'first-pw']));
        $this->patch(route('tool.mail.update'), $this->validPayload([
            'password' => '',
            'clear_password' => true,
        ]));

        $this->assertNull(app(MailSettings::class)->password);
    }

    #[Test]
    public function empty_fields_are_stored_as_null_to_fall_back_to_env(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload([
            'host' => '',
            'from_name' => '',
        ]));

        $settings = app(MailSettings::class);
        $this->assertNull($settings->host);
        $this->assertNull($settings->from_name);
    }

    #[Test]
    public function invalid_port_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload([
            'port' => 70000,
        ]))->assertSessionHasErrors('port');
    }

    #[Test]
    public function invalid_encryption_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload([
            'encryption' => 'starttls',
        ]))->assertSessionHasErrors('encryption');
    }

    #[Test]
    public function removing_an_override_restores_the_immutable_environment_fallback(): void
    {
        config(['mail.fallback.host' => 'smtp.fallback.example']);
        $settings = app(MailSettings::class);
        $settings->host = 'smtp.override.example';
        $settings->save();

        app()->getProvider(MailSettingsServiceProvider::class)?->applyMailConfig(true);
        $this->assertSame('smtp.override.example', config('mail.mailers.smtp.host'));

        $settings->host = null;
        $settings->save();
        app()->getProvider(MailSettingsServiceProvider::class)?->applyMailConfig(true);

        $this->assertSame('smtp.fallback.example', config('mail.mailers.smtp.host'));
    }

    #[Test]
    public function a_long_lived_worker_refreshes_mail_config_before_each_job(): void
    {
        $settings = app(MailSettings::class);
        $settings->host = 'smtp.worker.example';
        $settings->save();
        config(['mail.mailers.smtp.host' => 'smtp.stale.example']);
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn([]);

        event(new JobProcessing('sync', $job));

        $this->assertSame('smtp.worker.example', config('mail.mailers.smtp.host'));
    }

    #[Test]
    public function non_admin_cannot_update_mail_settings(): void
    {
        $this->actingAs(User::factory()->create());

        $this->patch(route('tool.mail.update'), $this->validPayload())->assertForbidden();
    }

    #[Test]
    public function audit_log_entry_is_created_on_update(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload());

        $this->assertTrue(
            Activity::query()->where('log_name', 'mail_settings')
                ->where('description', 'settings_updated')->exists()
        );
    }

    #[Test]
    public function test_mail_returns_success_when_transport_accepts_the_message(): void
    {
        $this->actingAsAdmin();

        // Use the in-memory array transport so no real network connection is attempted.
        config(['mail.mailers.smtp.transport' => 'array']);

        $this->postJson(route('tool.mail.test'), ['recipient' => 'target@example.com'])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function test_mail_returns_a_generic_error_message_on_failure(): void
    {
        $this->actingAsAdmin();

        // Real SMTP transport against an unresolvable host => real connection exception.
        config(['mail.mailers.smtp.transport' => 'smtp']);

        $settings = app(MailSettings::class);
        $settings->host = 'smtp.this-host-does-not-exist.invalid';
        $settings->port = 2525;
        $settings->save();

        $response = $this->postJson(route('tool.mail.test'), ['recipient' => 'target@example.com'])
            ->assertStatus(500)
            ->assertJson(['success' => false]);

        // Bewusst generisch: Die rohe Transport-Exception wäre ein
        // Port-Scan-Oracle (Host/Port frei wählbar, Fehlermeldung verrät
        // Erreichbarkeit). Details landen nur im Log.
        $this->assertNotEmpty($response->json('message'));
        $this->assertStringNotContainsString('smtp.this-host-does-not-exist.invalid', $response->json('message'));
    }

    #[Test]
    public function test_mail_requires_a_valid_recipient(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('tool.mail.test'), ['recipient' => 'not-an-email'])
            ->assertStatus(422);
    }
}
