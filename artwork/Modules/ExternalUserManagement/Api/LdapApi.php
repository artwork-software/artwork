<?php

namespace Artwork\Modules\ExternalUserManagement\Api;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Support\Collection;
use LdapRecord\Connection;
use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use LdapRecord\Query\Collection as LdapCollection;

class LdapApi implements ExternalUserManagementApi
{
    /**
     * Erstellt eine LDAP-Verbindung aus der Datenbank-Konfiguration
     */
    private function createConnection(ExternalUserSource $source): Connection
    {
        $config = $source->config ?? [];

        $connectionConfig = [
            'hosts' => [$this->extractHost($config['host'] ?? '')],
            'base_dn' => $config['base_dn'] ?? '',
            'username' => $config['bind_dn'] ?? '',
            'password' => $config['bind_password'] ?? '',
            'port' => $config['port'] ?? 389,
            'use_ssl' => $config['use_ssl'] ?? false,
            'use_tls' => $config['use_tls'] ?? false,
            'timeout' => $config['timeout'] ?? 5,
            'options' => [
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_REFERRALS => 0,
            ],
        ];

        return new Connection($connectionConfig);
    }

    /**
     * Extrahiert den Host aus einer LDAP-URL (z.B. ldaps://ad.domain.tld -> ad.domain.tld)
     */
    private function extractHost(string $host): string
    {
        // Entferne ldap:// oder ldaps:// Präfix
        $host = preg_replace('#^ldaps?://#', '', $host);
        
        // Entferne Port falls vorhanden
        $host = preg_replace('#:\d+$#', '', $host);

        return $host;
    }

    /**
     * Testet die LDAP-Verbindung
     */
    public function testConnection(ExternalUserSource $source): bool
    {
        $connection = $this->createConnection($source);

        try {
            $connection->connect();
            return true;
        } catch (\Exception $e) {
            // Exception für Sentry loggen
            if (app()->bound('sentry') && app()->environment() !== 'local' && !app()->runningUnitTests()) {
                app('sentry')->captureException($e);
            }
            // Exception weiterwerfen, damit sie auch vom Exception Handler geloggt wird
            // Aber false zurückgeben, damit das Frontend die Fehlermeldung anzeigen kann
            throw $e;
        }
        // Connection wird automatisch geschlossen wenn das Objekt zerstört wird
    }

    /**
     * Ruft alle Nutzer basierend auf dem konfigurierten Filter ab
     *
     * @return Collection<array{identifier: string, email: string, first_name: string, last_name: string, groups: array}>
     */
    public function fetchUsers(ExternalUserSource $source): Collection
    {
        $connection = $this->createConnection($source);
        $connection->connect();

        $config = $source->config ?? [];
        $baseDn = $config['base_dn'] ?? '';
        $filter = $config['user_filter'] ?? '(objectClass=user)';
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        $users = LdapUser::on($connection)
            ->in($baseDn)
            ->rawFilter($filter)
            ->get();

        return $users->map(function (LdapUser $ldapUser) use ($source, $connection, $identifierAttribute) {
            $identifier = $this->getAttributeValue($ldapUser, $identifierAttribute);
            $email = $this->getAttributeValue($ldapUser, 'mail');
            $firstName = $this->getAttributeValue($ldapUser, 'givenName') ?? '';
            $lastName = $this->getAttributeValue($ldapUser, 'sn') ?? '';
            
            // Hole Gruppenmitgliedschaften
            $groups = $this->fetchUserGroupsFromLdapUser($ldapUser, $source, $connection);

            return [
                'identifier' => $identifier,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'groups' => $groups,
                'meta_data' => [
                    'distinguished_name' => $ldapUser->getDn(),
                    'sam_account_name' => $this->getAttributeValue($ldapUser, 'sAMAccountName'),
                    'user_principal_name' => $this->getAttributeValue($ldapUser, 'userPrincipalName'),
                    'display_name' => $this->getAttributeValue($ldapUser, 'displayName'),
                    'department' => $this->getAttributeValue($ldapUser, 'department'),
                    'title' => $this->getAttributeValue($ldapUser, 'title'),
                    'telephone' => $this->getAttributeValue($ldapUser, 'telephoneNumber'),
                ],
            ];
        });
        // Connection wird automatisch geschlossen wenn das Objekt zerstört wird
    }

    /**
     * Ruft die Gruppenmitgliedschaften eines Nutzers ab (inkl. verschachtelter Gruppen)
     *
     * @return array<string> Array von Gruppen-DNs
     */
    public function fetchUserGroups(ExternalUserSource $source, string $userIdentifier, bool $includeNested = true): array
    {
        $connection = $this->createConnection($source);
        $connection->connect();

        $config = $source->config ?? [];
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        $user = LdapUser::on($connection)
            ->where($identifierAttribute, '=', $userIdentifier)
            ->first();

        if (!$user) {
            return [];
        }

        return $this->fetchUserGroupsFromLdapUser($user, $source, $connection, $includeNested);
        // Connection wird automatisch geschlossen wenn das Objekt zerstört wird
    }

    /**
     * Ruft Gruppenmitgliedschaften direkt vom LDAP-User-Objekt ab
     *
     * @return array<string>
     */
    private function fetchUserGroupsFromLdapUser(
        LdapUser $ldapUser,
        ExternalUserSource $source,
        Connection $connection,
        bool $includeNested = true
    ): array {
        $groups = [];
        $processedGroups = [];

        // Direkte Gruppenmitgliedschaften
        $memberOf = $ldapUser->getAttribute('memberOf') ?? [];
        
        foreach ($memberOf as $groupDn) {
            $groups[] = $groupDn;
            $processedGroups[$groupDn] = true;

            // Wenn verschachtelte Gruppen unterstützt werden sollen
            if ($includeNested) {
                $nestedGroups = $this->fetchNestedGroups($groupDn, $connection, $processedGroups);
                $groups = array_merge($groups, $nestedGroups);
            }
        }

        return array_unique($groups);
    }

    /**
     * Ruft verschachtelte Gruppen rekursiv ab
     *
     * @param array<string, bool> $processedGroups Vermeidet zirkuläre Referenzen
     * @return array<string>
     */
    private function fetchNestedGroups(string $groupDn, Connection $connection, array &$processedGroups = []): array
    {
        $nestedGroups = [];

        $group = Group::on($connection)->findByDn($groupDn);
        
        if (!$group) {
            return [];
        }

        $memberOf = $group->getAttribute('memberOf') ?? [];

        foreach ($memberOf as $nestedGroupDn) {
            // Vermeide zirkuläre Referenzen
            if (isset($processedGroups[$nestedGroupDn])) {
                continue;
            }

            $processedGroups[$nestedGroupDn] = true;
            $nestedGroups[] = $nestedGroupDn;

            // Rekursiv verschachtelte Gruppen abrufen
            $deeperNested = $this->fetchNestedGroups($nestedGroupDn, $connection, $processedGroups);
            $nestedGroups = array_merge($nestedGroups, $deeperNested);
        }

        return $nestedGroups;
    }

    /**
     * Authentifiziert einen Nutzer gegen LDAP
     */
    public function authenticate(ExternalUserSource $source, string $username, string $password): bool
    {
        $connection = $this->createConnection($source);

        $config = $source->config ?? [];
        $baseDn = $config['base_dn'] ?? '';
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        // Versuche den User zu finden
        $connection->connect();
        
        $user = LdapUser::on($connection)
            ->in($baseDn)
            ->where('sAMAccountName', '=', $username)
            ->orWhere('userPrincipalName', '=', $username)
            ->orWhere('mail', '=', $username)
            ->first();

        if (!$user) {
            return false;
        }

        // Versuche mit den User-Credentials zu authentifizieren
        $userDn = $user->getDn();
        $config = $source->config ?? [];
        
        $userConnectionConfig = [
            'hosts' => [$this->extractHost($config['host'] ?? '')],
            'base_dn' => $config['base_dn'] ?? '',
            'username' => $userDn,
            'password' => $password,
            'port' => $config['port'] ?? 389,
            'use_ssl' => $config['use_ssl'] ?? false,
            'use_tls' => $config['use_tls'] ?? false,
            'timeout' => $config['timeout'] ?? 5,
            'options' => [
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_REFERRALS => 0,
            ],
        ];
        
        $userConnection = new Connection($userConnectionConfig);
        $userConnection->connect();
        // Connection wird automatisch geschlossen wenn das Objekt zerstört wird

        return true;
        // Connection wird automatisch geschlossen wenn das Objekt zerstört wird
    }

    /**
     * Hilfsmethode um Attribute-Werte sicher abzurufen
     */
    private function getAttributeValue(LdapUser $ldapUser, string $attribute): ?string
    {
        $value = $ldapUser->getAttribute($attribute);
        
        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return $value;
    }
}
