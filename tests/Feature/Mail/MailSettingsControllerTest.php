<?php

namespace Tests\Feature\Mail;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Mail\Providers\MailSettingsServiceProvider;
use Artwork\Modules\Mail\Services\MailSettingsResolver;
use Artwork\Modules\Mail\Settings\MailSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\Concerns\InteractsWithInertia;
use Tests\TestCase;

final class MailSettingsControllerTest extends TestCase
{
    use InteractsWithInertia;

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
        // Ohne Benutzername: Ein Benutzername ohne Passwort wäre ein unvollständiges Paar.
        $this->patch(route('tool.mail.update'), $this->validPayload([
            'username' => '',
            'password' => '',
            'clear_password' => true,
        ]))->assertSessionHasNoErrors();

        $this->assertNull(app(MailSettings::class)->password);
    }

    #[Test]
    public function a_user_name_without_any_password_is_rejected(): void
    {
        config([
            'mail.fallback.host' => 'smtp.env.example',
            'mail.fallback.username' => 'env-user',
            'mail.fallback.password' => 'env-secret',
        ]);
        $this->actingAsAdmin();

        // Sentry (Theaterhaus Jena): Benutzername aus den Einstellungen + .env-Passwort → 535.
        $this->patch(route('tool.mail.update'), $this->validPayload([
            'host' => '',
            'username' => 'loth@theater.example',
            'password' => '',
        ]))->assertSessionHasErrors('password');

        $this->assertNull(app(MailSettings::class)->refresh()->username);
    }

    #[Test]
    public function clearing_the_password_of_a_custom_user_name_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload(['password' => 'first-pw']));
        $this->patch(route('tool.mail.update'), $this->validPayload([
            'password' => '',
            'clear_password' => true,
        ]))->assertSessionHasErrors('password');

        $this->assertSame('first-pw', app(MailSettings::class)->refresh()->password);
    }

    #[Test]
    public function the_env_user_name_on_the_env_host_may_keep_the_env_password(): void
    {
        config([
            'mail.fallback.host' => 'smtp.env.example',
            'mail.fallback.username' => 'env-user',
            'mail.fallback.password' => 'env-secret',
        ]);
        $this->actingAsAdmin();

        $this->patch(route('tool.mail.update'), $this->validPayload([
            'host' => 'SMTP.env.example',
            'username' => 'ENV-USER',
            'password' => '',
        ]))->assertSessionHasNoErrors();

        $this->assertNull(app(MailSettings::class)->refresh()->password);
    }

    #[Test]
    public function the_page_shows_where_the_smtp_login_comes_from(): void
    {
        config([
            'mail.fallback.host' => 'smtp.env.example',
            'mail.fallback.username' => 'env-user',
            'mail.fallback.password' => 'env-secret',
        ]);
        $settings = app(MailSettings::class);
        $settings->host = 'smtp.custom.example';
        $settings->username = 'custom-user';
        $settings->password = null;
        $settings->save();
        $this->actingAsAdmin();

        $response = $this->get(route('tool.mail'))->assertOk();
        $this->assertInertiaWhere($response, 'mailSettings.authentication.source', 'settings');
        $this->assertInertiaWhere($response, 'mailSettings.authentication.password_missing', true);

        $settings->host = null;
        $settings->username = null;
        $settings->save();

        $response = $this->get(route('tool.mail'))->assertOk();
        $this->assertInertiaWhere($response, 'mailSettings.authentication.source', 'env');
        $this->assertInertiaWhere($response, 'mailSettings.authentication.password_missing', false);
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
    public function test_mail_uses_the_same_sender_as_real_mails(): void
    {
        $this->actingAsAdmin();
        config([
            'mail.mailers.smtp.transport' => 'array',
            'mail.fallback.from_address' => 'hello@example.com',
        ]);
        $generalSettings = app(GeneralSettings::class);
        $generalSettings->business_email = 'support@theater.example';
        $generalSettings->save();
        $mailSettings = app(MailSettings::class);
        $mailSettings->from_address = null;
        $mailSettings->save();

        // Sentry (Theaterhaus Jena): Die Testmail kam von MAIL_FROM_ADDRESS (hello@example.com),
        // echte Mails von der Support-Adresse — der Test sagte nichts über echte Mails aus.
        $this->postJson(route('tool.mail.test'), ['recipient' => 'target@example.com'])->assertOk();

        $sent = Mail::mailer('smtp')->getSymfonyTransport()->messages();
        $this->assertSame(
            'support@theater.example',
            $sent->last()->getOriginalMessage()->getFrom()[0]->getAddress()
        );
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function smtpRejections(): array
    {
        return [
            'login rejected' => [
                'Failed to authenticate on SMTP server with username "loth@theater.example"',
                535,
                'The mail server rejected the login. Please check the user name and password.',
            ],
            'sender rejected' => [
                'Expected response code "250/251/252" but got code "554", with message "554 5.7.1 '
                . '<hello@example.com>: Sender address rejected: Access denied".',
                554,
                'The mail server rejected the sender address :address. Enter a sender address the mail account may send from.',
            ],
            'login required' => [
                'Expected response code "250/251/252" but got code "554", with message "554 5.7.1 '
                . '<static.example>: Client host rejected: Access denied".',
                554,
                'The mail server only accepts emails after login. Please enter user name and password.',
            ],
            'certificate' => [
                'Unable to connect with STARTTLS: OpenSSL Error messages: error:0A000086:SSL routines::certificate verify failed',
                0,
                'The TLS certificate of the mail server could not be verified.',
            ],
            'connection stays generic' => [
                'Connection could not be established with host "smtp.internal.example:25"',
                0,
                'Test email could not be sent. Please check the mail configuration and the log.',
            ],
        ];
    }

    #[Test]
    #[DataProvider('smtpRejections')]
    public function test_mail_names_the_cause_of_an_smtp_rejection(
        string $transportMessage,
        int $code,
        string $expectedMessage
    ): void {
        $this->actingAsAdmin();
        Mail::extend('rejecting', fn () => new class ($transportMessage, $code) extends AbstractTransport {
            public function __construct(private readonly string $rejection, private readonly int $rejectionCode)
            {
                parent::__construct();
            }

            protected function doSend(SentMessage $message): void
            {
                throw new TransportException($this->rejection, $this->rejectionCode);
            }

            public function __toString(): string
            {
                return 'rejecting://';
            }
        });
        config(['mail.mailers.smtp.transport' => 'rejecting']);

        $response = $this->postJson(route('tool.mail.test'), ['recipient' => 'target@example.com'])
            ->assertStatus(500)
            ->assertJson(['success' => false]);

        $this->assertSame(
            __($expectedMessage, ['address' => (string) app(MailSettingsResolver::class)->fromAddress()]),
            $response->json('message')
        );
        $this->assertStringNotContainsString('smtp.internal.example', $response->json('message'));
    }

    #[Test]
    public function test_mail_requires_a_valid_recipient(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('tool.mail.test'), ['recipient' => 'not-an-email'])
            ->assertStatus(422);
    }
}
