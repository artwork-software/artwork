<?php

namespace App\Http\Controllers;

use App\Providers\RouteServiceProvider;
use Artwork\Modules\ExternalUserManagement\Exceptions\ExternalIdentityDeletedException;
use Artwork\Modules\ExternalUserManagement\Exceptions\UnverifiedExternalEmailException;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserGroupMappingService;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserService;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\ExternalUserManagement\Support\OidcProviderPreset;
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

        return $this->multiTenantRejection($externalUserSource)
            ?? $this->oidcService->redirect($externalUserSource);
    }

    /**
     * Callback vom Identity Provider: Claims holen, Domain prüfen, User
     * per Just-in-Time-Provisionierung anlegen/aktualisieren, Rollen aus
     * dem groups-Claim syncen und den Nutzer einloggen.
     */
    public function callback(ExternalUserSource $externalUserSource): RedirectResponse
    {
        $this->assertUsableSource($externalUserSource);

        $rejection = $this->multiTenantRejection($externalUserSource);

        if ($rejection !== null) {
            return $rejection;
        }

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

        // Eine leere Allowlist verweigert den Zugriff sicherheitshalber vollständig.
        if (!$externalUserSource->allowsEmailDomain($email, false)) {
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
        } catch (UnverifiedExternalEmailException) {
            return redirect()->route('login')->withErrors([
                'email' => __('flash-messages.oidc.error.email_not_verified'),
            ]);
        } catch (ExternalIdentityDeletedException) {
            return redirect()->route('login')->withErrors([
                'email' => __('flash-messages.oidc.error.account_deleted'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => __('flash-messages.oidc.error.authentication_failed'),
            ]);
        }

        // Kein Remember-Me für SSO-Logins: erneuter Zugriff läuft über den IdP.
        Auth::guard(config('fortify.guard'))->login($user, false);

        // Session-Fixation verhindern: Fortifys eigener Login-Pfad regeneriert die
        // Session-ID ebenfalls – der manuelle OIDC-Login muss das selbst tun.
        request()->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    private function assertUsableSource(ExternalUserSource $source): void
    {
        abort_unless($source->active && $source->type === 'identity_provider', 404);
    }

    /**
     * Über common/organizations/consumers könnten sich Konten aus beliebigen fremden
     * Entra-Tenants anmelden; solche Bestandsquellen werden nicht mehr bedient.
     */
    private function multiTenantRejection(ExternalUserSource $source): ?RedirectResponse
    {
        if (!OidcProviderPreset::isMultiTenant($source->config ?? [])) {
            return null;
        }

        return redirect()->route('login')->withErrors([
            'email' => __('flash-messages.oidc.error.multi_tenant_not_allowed'),
        ]);
    }
}
