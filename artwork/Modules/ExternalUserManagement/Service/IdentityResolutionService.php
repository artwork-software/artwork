<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Exceptions\IdentityLinkConflictException;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Löst eine extern authentifizierte Identität (OIDC/LDAP) auf einen lokalen
 * artwork-User auf und legt bei Bedarf einen an – gemäß Spezifikation §3/§4:
 *
 *  1. Primär über auth_provider_id + Issuer.
 *  2. Erstverknüpfung über die E-Mail (bei OIDC nur mit verifiziertem Claim).
 *  3. Sonst Provisionierung eines IdP-gebundenen Accounts mit Default-Rolle.
 *
 * Der lokale Datensatz inkl. aller Relationen, Rollen und Berechtigungen bleibt
 * erhalten – der IdP ersetzt ausschließlich die Credential-Prüfung.
 */
class IdentityResolutionService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AdminLockoutGuard $adminLockoutGuard
    ) {
    }

    /**
     * @param array{first_name?: string, last_name?: string} $profile
     */
    public function resolveAndLink(
        ExternalUserSource $source,
        string $subjectId,
        ?string $email,
        bool $emailVerified,
        array $profile
    ): User {
        $provider = $source->providerKey();
        $issuer = $source->resolvedIssuer();

        // 1. Primärauflösung über den stabilen Subject-Identifier + Issuer.
        $user = $this->findByProviderIdentity($provider, $subjectId, $issuer);

        if ($user !== null) {
            return $this->refreshProfile($user, $email, $profile);
        }

        // 2. Erstverknüpfung über die E-Mail – bei OIDC nur mit verifiziertem Claim.
        if ($this->mayLinkByEmail($provider, $email, $emailVerified)) {
            $existing = $this->userRepository->getNewModelQuery()
                ->where('email', $email)
                ->first();

            if ($existing !== null) {
                return $this->linkExistingUser($existing, $provider, $subjectId, $issuer, $email, $profile);
            }
        }

        // 3. Provisionierung eines neuen, direkt IdP-gebundenen Accounts.
        return $this->provision($source, $provider, $subjectId, $issuer, $email, $profile);
    }

    private function findByProviderIdentity(string $provider, string $subjectId, ?string $issuer): ?User
    {
        return $this->userRepository->getNewModelQuery()
            ->where('auth_provider', $provider)
            ->where('auth_provider_id', $subjectId)
            ->where('auth_provider_issuer', $issuer)
            ->first();
    }

    private function mayLinkByEmail(string $provider, ?string $email, bool $emailVerified): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        // Ein unverifizierter OIDC-Claim darf nie auf einen bestehenden Account matchen.
        return $provider === 'ldap' || $emailVerified;
    }

    /**
     * @param array{first_name?: string, last_name?: string} $profile
     */
    private function linkExistingUser(
        User $existing,
        string $provider,
        string $subjectId,
        ?string $issuer,
        ?string $email,
        array $profile
    ): User {
        // Bereits an dieselbe Verbindung gebunden, nur mit neuem Subject → re-key.
        $sameConnection = $existing->isProvider($provider) && $existing->auth_provider_issuer === $issuer;

        if ($existing->isIdpBound() && !$sameConnection) {
            throw new IdentityLinkConflictException(
                'Email matches an account already bound to a different identity provider.'
            );
        }

        if (!$existing->isIdpBound()) {
            // Lokaler Account wird IdP-gebunden – Admin-Lockout verhindern.
            $this->adminLockoutGuard->assertNotLastLocalAdmin($existing);
        }

        $existing->auth_provider = $provider;
        $existing->auth_provider_id = $subjectId;
        $existing->auth_provider_issuer = $issuer;
        // Legacy-Felder additiv mitführen, damit der bestehende LDAP-Sync
        // und weitere Leser konsistent bleiben.
        $existing->ad_managed = true;
        $existing->ad_identifier = $subjectId;

        return $this->refreshProfile($existing, $email, $profile);
    }

    /**
     * @param array{first_name?: string, last_name?: string} $profile
     */
    private function provision(
        ExternalUserSource $source,
        string $provider,
        string $subjectId,
        ?string $issuer,
        ?string $email,
        array $profile
    ): User {
        if ($email === null || $email === '') {
            throw new \InvalidArgumentException('Cannot provision an external user without an email address');
        }

        // An dieser Stelle konnte weder über den Subject-Identifier noch über eine
        // verifizierte E-Mail verknüpft werden. Existiert die E-Mail dennoch bereits,
        // handelt es sich um einen unverifizierten Übernahmeversuch – abweisen.
        if ($this->userRepository->getNewModelQuery()->where('email', $email)->exists()) {
            throw new IdentityLinkConflictException(
                'Cannot link an external identity to an existing account without a verified email address.'
            );
        }

        /** @var User $user */
        $user = $this->userRepository->getNewModelInstance();
        $user->fill([
            'first_name' => $profile['first_name'] ?? '',
            'last_name' => $profile['last_name'] ?? '',
            'email' => $email,
            'password' => Hash::make(uniqid('', true)),
            'opened_checklists' => [],
            'opened_areas' => [],
            'ad_managed' => true,
            'ad_identifier' => $subjectId,
        ]);
        $user->auth_provider = $provider;
        $user->auth_provider_id = $subjectId;
        $user->auth_provider_issuer = $issuer;
        $this->userRepository->save($user);

        $this->assignDefaultRole($user, $source);

        return $user;
    }

    private function assignDefaultRole(User $user, ExternalUserSource $source): void
    {
        $roleId = $source->defaultRoleId();

        if ($roleId === null) {
            return;
        }

        $role = Role::find($roleId);

        if ($role !== null) {
            $user->assignRole($role);
        }
    }

    /**
     * @param array{first_name?: string, last_name?: string} $profile
     */
    private function refreshProfile(User $user, ?string $email, array $profile): User
    {
        $firstName = $profile['first_name'] ?? '';
        $lastName = $profile['last_name'] ?? '';

        if ($firstName !== '') {
            $user->first_name = $firstName;
        }

        if ($lastName !== '') {
            $user->last_name = $lastName;
        }

        if ($email !== null && $email !== '') {
            $user->email = $email;
        }

        $this->userRepository->save($user);

        return $user;
    }
}
