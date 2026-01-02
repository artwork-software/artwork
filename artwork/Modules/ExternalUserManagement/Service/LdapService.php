<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Api\LdapApi;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Support\Collection;

class LdapService
{
    public function __construct(
        private readonly LdapApi $ldapApi
    ) {
    }

    /**
     * Testet die LDAP-Verbindung
     */
    public function testConnection(ExternalUserSource $source): bool
    {
        return $this->ldapApi->testConnection($source);
    }

    /**
     * Ruft alle Nutzer basierend auf dem konfigurierten Filter ab
     *
     * @return Collection<array{identifier: string, email: string, first_name: string, last_name: string, groups: array}>
     */
    public function fetchUsers(ExternalUserSource $source): Collection
    {
        return $this->ldapApi->fetchUsers($source);
    }

    /**
     * Ruft die Gruppenmitgliedschaften eines Nutzers ab (inkl. verschachtelter Gruppen)
     *
     * @return array<string> Array von Gruppen-DNs
     */
    public function fetchUserGroups(ExternalUserSource $source, string $userIdentifier, bool $includeNested = true): array
    {
        return $this->ldapApi->fetchUserGroups($source, $userIdentifier, $includeNested);
    }

    /**
     * Authentifiziert einen Nutzer gegen LDAP
     */
    public function authenticate(ExternalUserSource $source, string $username, string $password): bool
    {
        return $this->ldapApi->authenticate($source, $username, $password);
    }
}

