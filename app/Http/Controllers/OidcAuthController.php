<?php

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserGroupMappingService;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserService;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OidcAuthController extends Controller
{
    public function __construct(
        private readonly OidcService $oidcService,
        private readonly ExternalUserService $externalUserService,
        private readonly ExternalUserGroupMappingService $groupMappingService
    ) {
    }

    /**
     * Leitet den Nutzer zum Identity Provider weiter (Authorization-Code-Flow).
     */
    public function redirect(ExternalUserSource $externalUserSource): RedirectResponse
    {
        $this->assertUsableSource($externalUserSource);

        return $this->oidcService->redirect($externalUserSource);
    }

    /**
     * Callback vom Identity Provider: Claims holen, Domain prüfen, User
     * per Just-in-Time-Provisionierung anlegen/aktualisieren, Rollen aus
     * dem groups-Claim syncen und den Nutzer einloggen.
     */
    public function callback(ExternalUserSource $externalUserSource): RedirectResponse
    {
        $this->assertUsableSource($externalUserSource);

        try {
            $claims = $this->oidcService->userFromCallback($externalUserSource);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => __('flash-messages.oidc.error.authentication_failed'),
            ]);
        }

        $email = $claims['email'] ?? null;

        if (!$email) {
            return redirect()->route('login')->withErrors([
                'email' => __('flash-messages.oidc.error.missing_email'),
            ]);
        }

        if (!$this->emailDomainAllowed($externalUserSource, $email)) {
            return redirect()->route('login')->withErrors([
                'email' => __('flash-messages.oidc.error.domain_not_allowed'),
            ]);
        }

        try {
            $user = DB::transaction(function () use ($externalUserSource, $claims): User {
                $user = $this->externalUserService->findOrCreateUser(
                    $externalUserSource,
                    $claims,
                    $claims['identifier']
                );

                $this->externalUserService->findOrCreateExternalUser(
                    $externalUserSource,
                    $claims['identifier'],
                    $claims['meta_data'],
                    $user
                );

                $this->externalUserService->syncUserGroups(
                    $externalUserSource,
                    $user,
                    $claims['groups'],
                    $this->groupMappingService
                );

                if (($claims['email_verified'] ?? false) && $user->email_verified_at === null) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                return $user;
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => __('flash-messages.oidc.error.authentication_failed'),
            ]);
        }

        Auth::guard(config('fortify.guard'))->login($user, true);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    private function assertUsableSource(ExternalUserSource $source): void
    {
        abort_unless($source->active && $source->type === 'identity_provider', 404);
    }

    /**
     * Erzwingt die optionale, pro-Quelle konfigurierte E-Mail-Domain-Allowlist.
     * Ist keine Allowlist konfiguriert, ist jede Domain zugelassen.
     */
    private function emailDomainAllowed(ExternalUserSource $source, string $email): bool
    {
        $allowed = $source->config['allowed_domains'] ?? [];

        if (empty($allowed)) {
            return true;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '@', 1));
        $allowed = array_map(
            static fn ($entry): string => strtolower(ltrim(trim((string) $entry), '@')),
            (array) $allowed
        );

        return in_array($domain, $allowed, true);
    }
}
