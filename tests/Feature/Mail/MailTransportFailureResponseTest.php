<?php

namespace Tests\Feature\Mail;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * Sentry HAU-ARTWORK-65 / BERLINISCHE-GALERIE-3B: ein nicht erreichbarer Mailserver führte bei
 * „Passwort vergessen“ und Reset-Links zur 500-Seite.
 */
class MailTransportFailureResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::extend('failing', static fn () => new class () extends AbstractTransport {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('Connection to "192.168.1.10:443" timed out.');
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('Connection to "192.168.1.10:443" timed out.');
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        });
        config([
            'mail.mailers.failing' => ['transport' => 'failing'],
            'mail.default' => 'failing',
        ]);
    }

    #[Test]
    public function guest_forgot_password_shows_a_form_error_instead_of_a_server_error(): void
    {
        $user = User::factory()->create();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function logged_in_reset_link_request_shows_a_flash_error(): void
    {
        $admin = $this->actingAsAdmin();

        $this->from(route('users'))
            ->post(route('user.reset.password'), ['email' => $admin->email])
            ->assertRedirect(route('users'))
            ->assertSessionHas('error');
    }

    #[Test]
    public function json_requests_get_a_503_with_message(): void
    {
        $admin = $this->actingAsAdmin();

        $this->postJson(route('user.reset.password'), ['email' => $admin->email])
            ->assertStatus(503)
            ->assertJsonStructure(['message']);
    }
}
