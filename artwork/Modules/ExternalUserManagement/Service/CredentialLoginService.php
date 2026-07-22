<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Exceptions\AdminLockoutException;
use Artwork\Modules\ExternalUserManagement\Exceptions\IdentityLinkConflictException;
use Artwork\Modules\ExternalUserManagement\Exceptions\LdapAuthenticationFailedException;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Repository\ExternalUserSourceRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Einziger Credential-Pfad des Passwortformulars. Trennt drei Fälle:
 *
 *  - lokale Accounts prüfen ihren lokalen Passwort-Hash,
 *  - OIDC-gebundene Accounts sind am Passwortformular gesperrt (nur SSO-Button),
 *  - LDAP-Accounts (bekannt oder neu) verifizieren per Bind gegen das Directory.
 *
 * Ein lokaler Account, dessen Passwort nicht passt, kann per erfolgreichem
 * LDAP-Bind erstverknüpft werden (Erstverknüpfung über E-Mail).
 */
class CredentialLoginService
{
    public function __construct(
        private readonly ExternalUserSourceRepository $sourceRepository,
        private readonly LdapService $ldapService,
        private readonly IdentityResolutionService $identityResolutionService
    ) {
    }

    /**
     * @throws ValidationException wenn der Account IdP-gebunden ist und kein
     *         Passwort-Login erlaubt ist.
     */
    public function attempt(string $email, string $password): ?User
    {
        $email = trim($email);

        if ($email === '' || $password === '') {
            return null;
        }

        $user = User::query()->where('email', $email)->first();

        // OIDC-Accounts melden sich ausschließlich über den SSO-Button an – das
        // Flag ist am Passwortformular die einzige verfügbare OIDC-Information.
        if ($user !== null && $user->isProvider('oidc')) {
            throw ValidationException::withMessages([
                'email' => __('flash-messages.oidc.error.password_login_disabled'),
            ]);
        }

        // Zuerst den IdP prüfen: Existiert der Nutzer in einer aktiven LDAP-Quelle,
        // zählt ausschließlich der IdP – der lokale Passwort-Login greift dann nicht,
        // auch nicht bei falschem Passwort.
        $existsInIdp = false;
        $idpUser = $this->tryLdap($email, $password, $existsInIdp);

        if ($idpUser !== null) {
            return $idpUser;
        }

        if ($existsInIdp) {
            // Im Verzeichnis vorhanden, aber Bind/Domain fehlgeschlagen → abgelehnt.
            return null;
        }

        // Nicht im IdP → ganz normaler lokaler Login (Standardverhalten).
        if ($user !== null && Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }

    /**
     * Prüft den Login gegen die aktiven LDAP-Quellen.
     *
     * @param bool $existsInIdp wird auf true gesetzt, sobald der Nutzer in einer
     *        Quelle gefunden wurde – unabhängig davon, ob die Authentifizierung
     *        gelang. Der Aufrufer unterdrückt dann den lokalen Fallback.
     */
    private function tryLdap(string $email, string $password, bool &$existsInIdp): ?User
    {
        foreach ($this->sourceRepository->getAllActiveLdapSources() as $source) {
            /** @var ExternalUserSource $source */
            try {
                $profile = $this->ldapService->authenticateAndFetch($source, $email, $password);
            } catch (LdapAuthenticationFailedException $e) {
                // Nutzer existiert im Verzeichnis, Passwort ist falsch → nur IdP zählt.
                $existsInIdp = true;

                return null;
            } catch (\Throwable $e) {
                // Eine nicht erreichbare/fehlkonfigurierte Quelle darf den Login
                // nicht abbrechen – nächste aktive Quelle versuchen.
                report($e);
                continue;
            }

            if ($profile === null || empty($profile['identifier'])) {
                // In dieser Quelle nicht gefunden.
                continue;
            }

            // Ab hier existiert der Nutzer im IdP und wurde authentifiziert.
            $existsInIdp = true;
            $resolvedEmail = $profile['email'] ?? $email;

            if (!$this->emailDomainAllowed($source, $resolvedEmail)) {
                return null;
            }

            try {
                return $this->identityResolutionService->resolveAndLink(
                    $source,
                    (string) $profile['identifier'],
                    $resolvedEmail,
                    true,
                    [
                        'first_name' => $profile['first_name'] ?? '',
                        'last_name' => $profile['last_name'] ?? '',
                    ]
                );
            } catch (AdminLockoutException $e) {
                // Der letzte lokale Admin darf nicht ans Directory gebunden werden.
                // Das Konto muss lokal bleiben → lokalen Passwort-Fallback zulassen,
                // sonst wäre genau dieser Account komplett ausgesperrt.
                report($e);
                $existsInIdp = false;

                return null;
            } catch (IdentityLinkConflictException $e) {
                // E-Mail gehört zu einem Konto, das an eine andere Quelle gebunden
                // ist → Login ablehnen statt 500.
                report($e);

                return null;
            } catch (\Throwable $e) {
                // Verknüpfung/Sync fehlgeschlagen (z. B. E-Mail-Unique-Kollision)
                // → Login ablehnen statt 500.
                report($e);

                return null;
            }
        }

        return null;
    }

    /**
     * Optionale, pro-Quelle konfigurierte E-Mail-Domain-Allowlist.
     * Ohne Allowlist ist jede Domain zugelassen.
     */
    private function emailDomainAllowed(ExternalUserSource $source, ?string $email): bool
    {
        $allowed = $source->config['allowed_domains'] ?? [];

        if (empty($allowed)) {
            return true;
        }

        if ($email === null || $email === '') {
            return false;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '@', 1));
        $allowed = array_map(
            static fn ($entry): string => strtolower(ltrim(trim((string) $entry), '@')),
            (array) $allowed
        );

        return in_array($domain, $allowed, true);
    }
}
