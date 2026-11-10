<?php

namespace Artwork\Modules\GeneralSettings\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Einmal-Token für das Web-Setup (erster Admin). Ohne Token würde der erste Besucher einer frisch
 * deployten, öffentlich erreichbaren Instanz Admin. Gültig ist entweder der per ARTWORK_SETUP_TOKEN
 * gesetzte Wert oder ein erzeugter Token, von dem nur der Hash unter storage/ liegt; den Klartext
 * sieht nur, wer Zugriff auf Server-Log oder Konsole hat.
 */
class SetupTokenService
{
    public function configuredToken(): ?string
    {
        $token = config('security.setup_token');

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
    }

    public function hasToken(): bool
    {
        return $this->configuredToken() !== null || File::exists($this->hashPath());
    }

    /**
     * Erzeugt einen Token, falls weder ein ENV-Token noch ein gespeicherter Hash existiert.
     * Liefert den Klartext nur beim Erzeugen, sonst null.
     */
    public function ensureToken(): ?string
    {
        if ($this->hasToken()) {
            return null;
        }

        return $this->regenerate();
    }

    public function regenerate(): string
    {
        $token = Str::random(40);

        File::ensureDirectoryExists(dirname($this->hashPath()));
        File::put($this->hashPath(), hash('sha256', $token));
        File::chmod($this->hashPath(), 0600);

        return $token;
    }

    public function isValid(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $configured = $this->configuredToken();
        if ($configured !== null) {
            return hash_equals($configured, $token);
        }

        if (!File::exists($this->hashPath())) {
            return false;
        }

        return hash_equals(trim(File::get($this->hashPath())), hash('sha256', $token));
    }

    public function clear(): void
    {
        File::delete($this->hashPath());
    }

    public function setupUrl(string $token): string
    {
        return route('setup', ['token' => $token]);
    }

    private function hashPath(): string
    {
        return storage_path('app/setup-token.sha256');
    }
}
