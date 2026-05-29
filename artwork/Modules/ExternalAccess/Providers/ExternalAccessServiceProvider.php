<?php

namespace Artwork\Modules\ExternalAccess\Providers;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\Services\SourceEntityFactoryRegistry;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider as ServiceProviderModel;
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

        $this->app->singleton(SourceEntityFactoryRegistry::class);
    }

    public function boot(): void
    {
        $this->registerRateLimiters();
        $this->registerSourceEntityFactories();
    }

    /**
     * Explicit whitelist of invitable contact types. Each factory builds and persists the
     * source entity from the inviter-supplied public fields. Slugs match the entities'
     * getCrmContactTypeSlug() so createCrmContact() resolves the same CRM contact type.
     */
    private function registerSourceEntityFactories(): void
    {
        /** @var SourceEntityFactoryRegistry $registry */
        $registry = $this->app->make(SourceEntityFactoryRegistry::class);

        $registry->register(
            'freelancer',
            fn (InviteExternalCommand $cmd): Freelancer => Freelancer::create([
                'first_name' => $cmd->publicFieldValues['first_name'] ?? '',
                'last_name' => $cmd->publicFieldValues['last_name'] ?? '',
                'email' => $cmd->normalizedEmail(),
            ]),
            ['first_name', 'last_name'],
        );

        $registry->register(
            'service_provider',
            fn (InviteExternalCommand $cmd): ServiceProviderModel => ServiceProviderModel::create([
                'provider_name' => $cmd->publicFieldValues['provider_name'] ?? '',
                'email' => $cmd->normalizedEmail(),
            ]),
            ['provider_name'],
        );

        $registry->register(
            'artist',
            // Artist has no email column; the email lives only on ExternalAccess.
            fn (InviteExternalCommand $cmd): Artist => Artist::create([
                'name' => $cmd->publicFieldValues['name'] ?? '',
                'first_name' => $cmd->publicFieldValues['first_name'] ?? null,
                'last_name' => $cmd->publicFieldValues['last_name'] ?? null,
            ]),
            ['name'],
        );

        $registry->register(
            'manufacturer',
            fn (InviteExternalCommand $cmd): Manufacturer => Manufacturer::create([
                'name' => $cmd->publicFieldValues['name'] ?? '',
                'email' => $cmd->normalizedEmail(),
                'contact_person' => $cmd->publicFieldValues['contact_person'] ?? null,
            ]),
            ['name'],
        );

        $registry->register(
            'accommodation',
            fn (InviteExternalCommand $cmd): Accommodation => Accommodation::create([
                'name' => $cmd->publicFieldValues['name'] ?? '',
                'email' => $cmd->normalizedEmail(),
            ]),
            ['name'],
        );
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('external-request-link', function (Request $request) {
            $email = strtolower((string) $request->input('email'));
            $limits = app(\Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver::class)
                ->rateLimits();
            return [
                Limit::perHour($limits['request_link_per_email_per_hour'])
                    ->by('external-link:email:' . $email),
                Limit::perHour($limits['request_link_per_ip_per_hour'])
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
