<?php

namespace Artwork\Modules\Mail\Services;

use Artwork\Modules\Mail\Settings\MailSettings;

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
        // Kein Credential-Mixing: Bei überschriebenem Host niemals die
        // .env-Zugangsdaten verwenden — sonst würden die Server-Credentials
        // an einen frei konfigurierbaren Fremd-Host gesendet.
        if ($this->hasHostOverride()) {
            return $this->override($this->settings->username);
        }

        return $this->override($this->settings->username) ?? $this->usernameFallback();
    }

    public function usernameFallback(): ?string
    {
        return config('mail.fallback.username');
    }

    public function password(): ?string
    {
        if ($this->hasHostOverride()) {
            return $this->override($this->settings->password);
        }

        return $this->override($this->settings->password) ?? config('mail.fallback.password');
    }

    public function hasHostOverride(): bool
    {
        return $this->override($this->settings->host) !== null;
    }

    public function fromAddress(): ?string
    {
        return $this->override($this->settings->from_address) ?? $this->fromAddressFallback();
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
        return config('mail.fallback.from_address');
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
