<?php

namespace Artwork\Modules\Mail\Providers;

use Artwork\Modules\Mail\Services\MailSettingsResolver;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Address;
use Throwable;

class MailSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->applyMailConfig();

        Queue::before(function (): void {
            $this->applyMailConfig(true);
            Mail::purge('smtp');
        });

        // Fast alle Notifications setzen ihren Absender explizit (business_email
        // bzw. system_mail) — config('mail.from') greift dort nie. Eine im
        // Mail-Tab gepflegte From-Adresse muss deshalb beim Versand zentral
        // durchgesetzt werden, sonst ist das Setting wirkungslos (und der
        // Test-Mail-Erfolg sagt nichts über echte Mails aus).
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            try {
                $resolver = $this->app->make(MailSettingsResolver::class);
                $fromAddress = $resolver->fromAddressOverride();
            } catch (Throwable) {
                return;
            }

            if ($fromAddress === null) {
                return;
            }

            $existingFrom = $event->message->getFrom()[0] ?? null;
            $fromName = $resolver->fromNameOverride() ?? $existingFrom?->getName() ?? '';

            $event->message->from(new Address($fromAddress, $fromName));
        });
    }

    /**
     * Push the effective (admin-maintained, otherwise .env) mail configuration into
     * config('mail.*') so it applies to every outgoing mail. Deliberately NOT cached:
     * the settings read is a single cheap query per request, and caching would put
     * the decrypted SMTP password in plain text into the shared cache store.
     */
    public function applyMailConfig(bool $refreshSettings = false): void
    {
        try {
            $resolver = $this->app->make(MailSettingsResolver::class);
            if ($refreshSettings) {
                $resolver->refreshSettings();
            }

            $config = $resolver->effectiveConfigArray();
        } catch (Throwable) {
            // Settings table may not exist yet (early boot / migrations) — keep .env config.
            return;
        }

        Config::set('mail.mailers.smtp.host', $config['host']);
        Config::set('mail.mailers.smtp.port', $config['port']);
        Config::set('mail.mailers.smtp.encryption', $config['encryption']);
        // Laravel liest 'encryption' nicht mehr aus — wirksam sind nur 'scheme'
        // ('smtps' = implizites TLS, sonst entscheidet allein der Port) und
        // 'require_tls' (erzwungenes STARTTLS statt opportunistischem).
        Config::set('mail.mailers.smtp.scheme', $config['encryption'] === 'ssl' ? 'smtps' : null);
        Config::set('mail.mailers.smtp.require_tls', $config['encryption'] === 'tls');
        Config::set('mail.mailers.smtp.username', $config['username']);
        Config::set('mail.mailers.smtp.password', $config['password']);
        Config::set('mail.from.address', $config['from_address']);
        Config::set('mail.from.name', $config['from_name']);
    }
}
