<?php

namespace Artwork\Modules\Mail\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Mail\Settings\MailSettings;
use Throwable;

/**
 * Single source of truth for the "effective" mail configuration.
 * Reads from MailSettings (admin-configurable), falling back to config/mail.php (.env).
 */
class MailSettingsResolver
{
    public function __construct(
        private readonly MailSettings $settings,
    ) {
    }

    public function host(): ?string
    {
        return $this->override($this->settings->host) ?? $this->hostFallback();
    }

    public function hostFallback(): ?string
    {
        return config('mail.fallback.host');
    }

    public function port(): ?int
    {
        return $this->settings->port ?? $this->portFallback();
    }

    public function portFallback(): ?int
    {
        $port = config('mail.fallback.port');

        return $port !== null ? (int) $port : null;
    }

    public function encryption(): ?string
    {
        return $this->override($this->settings->encryption) ?? $this->encryptionFallback();
    }

    public function encryptionFallback(): ?string
    {
        return config('mail.fallback.encryption');
    }

    public function username(): ?string
    {
        // Kein Credential-Mixing: Bei einem anderen Host als in der .env niemals die
        // .env-Zugangsdaten verwenden — sonst würden die Server-Credentials
        // an einen frei konfigurierbaren Fremd-Host gesendet.
        return $this->override($this->settings->username)
            ?? ($this->usesFallbackHost($this->settings->host) ? $this->usernameFallback() : null);
    }

    public function usernameFallback(): ?string
    {
        return config('mail.fallback.username');
    }

    public function password(): ?string
    {
        $password = $this->override($this->settings->password);
        if ($password !== null) {
            return $password;
        }

        return $this->mayUseFallbackPassword($this->settings->host, $this->settings->username)
            ? $this->override(config('mail.fallback.password'))
            : null;
    }

    /**
     * Das .env-Passwort gehört zum .env-Konto auf dem .env-Host. Es darf nur mit genau diesem
     * Paar verwendet werden — ein in den Einstellungen eingetragener anderer Benutzername plus
     * .env-Passwort scheitert sonst mit „535 authentication failed“ (Sentry, Theaterhaus Jena).
     */
    public function mayUseFallbackPassword(?string $host, ?string $username): bool
    {
        if (!$this->usesFallbackHost($host)) {
            return false;
        }

        $username = $this->override($username);

        return $username === null || strcasecmp($username, (string) $this->usernameFallback()) === 0;
    }

    /**
     * Kein Host-Override oder derselbe Host wie in der .env (Groß-/Kleinschreibung egal).
     */
    public function usesFallbackHost(?string $host): bool
    {
        $host = $this->override($host);

        return $host === null || strcasecmp($host, (string) $this->hostFallback()) === 0;
    }

    public function hasHostOverride(): bool
    {
        return $this->override($this->settings->host) !== null;
    }

    /**
     * Woher die SMTP-Anmeldung effektiv kommt — für die Anzeige in den Einstellungen.
     *
     * @return 'settings'|'env'|'none'
     */
    public function authenticationSource(): string
    {
        if ($this->override($this->settings->username) !== null) {
            return 'settings';
        }

        return $this->username() !== null ? 'env' : 'none';
    }

    public function fromAddress(): ?string
    {
        return $this->override($this->settings->from_address) ?? $this->defaultSenderAddress();
    }

    /**
     * Absender ohne From-Override — dieselbe Kette wie in den Notifications (Support-Adresse aus
     * „Kommunikation & Rechtliches“, sonst system_mail), zuletzt MAIL_FROM_ADDRESS. Leere Werte
     * werden übersprungen, damit keine Mail ohne From-Header entsteht.
     */
    public function defaultSenderAddress(): ?string
    {
        try {
            $businessEmail = app(GeneralSettings::class)->business_email;
        } catch (Throwable) {
            $businessEmail = null;
        }

        return $this->override($businessEmail)
            ?? $this->override(config('mail.system_mail'))
            ?? $this->fromAddressFallback();
    }

    /**
     * Nur der explizit gepflegte Override (ohne .env-Fallback) — Basis für das
     * Absender-Rewrite beim Versand: Notifications setzen ihren Absender selbst
     * (business_email), nur eine bewusst gesetzte From-Adresse darf das übersteuern.
     */
    public function fromAddressOverride(): ?string
    {
        return $this->override($this->settings->from_address);
    }

    public function fromNameOverride(): ?string
    {
        return $this->override($this->settings->from_name);
    }

    public function fromAddressFallback(): ?string
    {
        return $this->override(config('mail.fallback.from_address'));
    }

    public function fromName(): ?string
    {
        return $this->override($this->settings->from_name) ?? $this->fromNameFallback();
    }

    public function fromNameFallback(): ?string
    {
        return config('mail.fallback.from_name');
    }

    /**
     * Effective SMTP + from configuration, used to push values into config('mail.*') at runtime.
     *
     * @return array<string, mixed>
     */
    public function effectiveConfigArray(): array
    {
        return [
            'host' => $this->host(),
            'port' => $this->port(),
            'encryption' => $this->encryption(),
            'username' => $this->username(),
            'password' => $this->password(),
            'from_address' => $this->fromAddress(),
            'from_name' => $this->fromName(),
        ];
    }

    public function refreshSettings(): void
    {
        $this->settings->refresh();
    }

    /**
     * Treats null / empty string as "not set" so the fallback kicks in.
     */
    private function override(?string $value): ?string
    {
        return ($value !== null && $value !== '') ? $value : null;
    }
}
