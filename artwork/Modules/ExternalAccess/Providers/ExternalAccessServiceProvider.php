<?php

namespace Artwork\Modules\ExternalAccess\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ExternalAccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 4) . '/config/external_access.php',
            'external_access'
        );
    }

    public function boot(): void
    {
        $this->registerRateLimiters();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('external-request-link', function (Request $request) {
            $email = strtolower((string) $request->input('email'));
            return [
                Limit::perHour((int) config('external_access.rate_limits.request_link_per_email_per_hour'))
                    ->by('external-link:email:' . $email),
                Limit::perHour((int) config('external_access.rate_limits.request_link_per_ip_per_hour'))
                    ->by('external-link:ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('external-redeem-token', function (Request $request) {
            return Limit::perMinute((int) config('external_access.rate_limits.redeem_token_per_ip_per_minute'))
                ->by('external-redeem:ip:' . $request->ip());
        });

        RateLimiter::for('external', function (Request $request) {
            $external = $request->user('external');
            if ($external !== null) {
                return Limit::perMinute(
                    (int) config('external_access.rate_limits.general_per_external_per_minute')
                )->by('external:' . $external->getAuthIdentifier());
            }
            return Limit::perMinute(20)->by((string) $request->ip());
        });
    }
}
