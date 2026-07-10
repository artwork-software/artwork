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

    public function testConnection(ExternalUserSource $source): bool
    {
        return $this->ldapApi->testConnection($source);
    }

    public function fetchUsers(ExternalUserSource $source): Collection
    {
        return $this->ldapApi->fetchUsers($source);
    }

    public function fetchUserGroups(
        ExternalUserSource $source,
        string $userIdentifier,
        bool $includeNested = true
    ): array {
        return $this->ldapApi->fetchUserGroups($source, $userIdentifier, $includeNested);
    }

    public function authenticate(ExternalUserSource $source, string $username, string $password): bool
    {
        return $this->ldapApi->authenticate($source, $username, $password);
    }
}

