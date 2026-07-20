<?php

namespace Artwork\Modules\Mail\Providers;

use Artwork\Modules\Mail\Services\MailSettingsResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Throwable;

class MailSettingsServiceProvider extends ServiceProvider
{
    public const CACHE_KEY = 'mail_settings_effective';
    private const CACHE_TTL_HOURS = 6;

    public function boot(): void
    {
        $this->applyMailConfig();
    }

    /**
     * Push the effective (admin-maintained, otherwise .env) mail configuration into
     * config('mail.*') so it applies to every outgoing mail. Cached because the mail
     * account is persistent; the cache is invalidated whenever the settings are saved.
     */
    public function applyMailConfig(): void
    {
        try {
            $config = Cache::remember(
                self::CACHE_KEY,
                now()->addHours(self::CACHE_TTL_HOURS),
                fn (): array => $this->app->make(MailSettingsResolver::class)->effectiveConfigArray()
            );
        } catch (Throwable) {
            // Settings table may not exist yet (early boot / migrations) — keep .env config.
            return;
        }

        if (($config['host'] ?? null) === null || $config['host'] === '') {
            return;
        }

        Config::set('mail.mailers.smtp.host', $config['host']);

        if ($config['port'] !== null) {
            Config::set('mail.mailers.smtp.port', $config['port']);
        }

        Config::set('mail.mailers.smtp.encryption', $config['encryption']);
        Config::set('mail.mailers.smtp.username', $config['username']);
        Config::set('mail.mailers.smtp.password', $config['password']);

        if (($config['from_address'] ?? null) !== null && $config['from_address'] !== '') {
            Config::set('mail.from.address', $config['from_address']);
        }

        if (($config['from_name'] ?? null) !== null && $config['from_name'] !== '') {
            Config::set('mail.from.name', $config['from_name']);
        }
    }
}
