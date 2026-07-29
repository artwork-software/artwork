<?php

namespace Artwork\Modules\ExternalUserManagement\Api;

use Artwork\Modules\ExternalUserManagement\Exceptions\LdapAuthenticationFailedException;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Support\Collection;
use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\Entry as LdapUser;
use LdapRecord\Query\Collection as LdapCollection;

class LdapApi implements ExternalUserManagementApi
{
    /** @var array<string, array<int, string>> */
    private array $groupParentCache = [];

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
     * Registriert die Verbindung im LdapRecord-Container und liefert deren Namen zurück.
     * Model::on() erwartet einen Connection-Namen (string), kein Connection-Objekt.
     */
    private function registerConnection(ExternalUserSource $source): string
    {
        $name = 'external_user_source_' . $source->getKey();
        Container::getInstance()->addConnection($this->createConnection($source), $name);

        return $name;
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
     * Normalisiert einen LDAP-Suchfilter.
     * LDAP verlangt umschließende Klammern – ein Filter wie "objectClass=user" ohne
     * Klammern führt zu "Bad search filter". Leerer Filter -> sinnvoller Default.
     */
    private function normalizeFilter(?string $filter): string
    {
        $filter = trim((string) $filter);

        if ($filter === '') {
            // Portable across Active Directory, OpenLDAP and other LDAP servers. AD-specific
            // installations can still exclude computer accounts via an explicit user_filter.
            return '(objectClass=person)';
        }

        if (!str_starts_with($filter, '(')) {
            $filter = '(' . $filter . ')';
        }

        return $filter;
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
            report($e);
            throw $e;
        }
        // Connection wird automatisch geschlossen wenn das Objekt zerstört wird
    }

    public function fetchUsers(ExternalUserSource $source): Collection
    {
        $this->groupParentCache = [];
        $connectionName = $this->registerConnection($source);

        $config = $source->config ?? [];
        $baseDn = $config['base_dn'] ?? '';
        $filter = $this->normalizeFilter($config['user_filter'] ?? null);
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        $users = LdapUser::on($connectionName)
            ->select([
                '*',
                $identifierAttribute,
                'mail',
                'givenName',
                'sn',
                'memberOf',
                'sAMAccountName',
                'userPrincipalName',
                'displayName',
                'department',
                'title',
                'telephoneNumber',
            ])
            ->in($baseDn)
            ->rawFilter($filter)
            ->get();

        return $users->map(function (LdapUser $ldapUser) use ($source, $connectionName, $identifierAttribute) {
            $identifier = $this->getAttributeValue($ldapUser, $identifierAttribute);
            $email = $this->getAttributeValue($ldapUser, 'mail');
            $firstName = $this->getAttributeValue($ldapUser, 'givenName') ?? '';
            $lastName = $this->getAttributeValue($ldapUser, 'sn') ?? '';

            // Hole Gruppenmitgliedschaften
            $groups = $this->fetchUserGroupsFromLdapUser($ldapUser, $source, $connectionName);

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
    }

    /**
     * Liefert die ersten $limit gefundenen User (ohne Gruppen-Auflösung) für den
     * Verbindungstest – schnell und ohne teure verschachtelte Gruppen-Rekursion.
     *
     * @return array<int, array<string, mixed>>
     */
    public function previewUsers(ExternalUserSource $source, int $limit = 3): array
    {
        $connectionName = $this->registerConnection($source);

        $config = $source->config ?? [];
        $baseDn = $config['base_dn'] ?? '';
        $filter = $this->normalizeFilter($config['user_filter'] ?? null);
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        $query = LdapUser::on($connectionName)
            ->select(['*', $identifierAttribute, 'mail', 'givenName', 'sn', 'displayName', 'cn'])
            ->rawFilter($filter)
            ->limit($limit);

        if ($baseDn !== '') {
            $query->in($baseDn);
        }

        return $query->get()
            ->take($limit)
            ->map(fn (LdapUser $ldapUser): array => [
                'identifier' => $this->getAttributeValue($ldapUser, $identifierAttribute),
                'email' => $this->getAttributeValue($ldapUser, 'mail'),
                'first_name' => $this->getAttributeValue($ldapUser, 'givenName') ?? '',
                'last_name' => $this->getAttributeValue($ldapUser, 'sn') ?? '',
                'display_name' => $this->getAttributeValue($ldapUser, 'displayName'),
                'dn' => $ldapUser->getDn(),
            ])
            ->values()
            ->all();
    }

    public function fetchUserGroups(
        ExternalUserSource $source,
        string $userIdentifier,
        bool $includeNested = true,
    ): array
    {
        $this->groupParentCache = [];
        $connectionName = $this->registerConnection($source);

        $config = $source->config ?? [];
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        $user = LdapUser::on($connectionName)
            ->where($identifierAttribute, '=', $userIdentifier)
            ->first();

        if (!$user) {
            return [];
        }

        return $this->fetchUserGroupsFromLdapUser($user, $source, $connectionName, $includeNested);
    }

    private function fetchUserGroupsFromLdapUser(
        LdapUser $ldapUser,
        ExternalUserSource $source,
        string $connectionName,
        bool $includeNested = true
    ): array {
        $groups = [];
        $processedGroups = [];

        $memberOf = $ldapUser->getAttribute('memberOf') ?? [];

        foreach ($memberOf as $groupDn) {
            $groups[] = $groupDn;
            $processedGroups[$groupDn] = true;

            if ($includeNested) {
                $nestedGroups = $this->fetchNestedGroups($groupDn, $connectionName, $processedGroups);
                $groups = array_merge($groups, $nestedGroups);
            }
        }

        return array_unique($groups);
    }

    private function fetchNestedGroups(string $groupDn, string $connectionName, array &$processedGroups = []): array
    {
        $nestedGroups = [];

        $cacheKey = $connectionName . ':' . mb_strtolower($groupDn);
        if (!array_key_exists($cacheKey, $this->groupParentCache)) {
            $group = LdapUser::on($connectionName)->findByDn($groupDn);
            $this->groupParentCache[$cacheKey] = $group?->getAttribute('memberOf') ?? [];
        }

        $memberOf = $this->groupParentCache[$cacheKey];

        foreach ($memberOf as $nestedGroupDn) {
            if (isset($processedGroups[$nestedGroupDn])) {
                continue;
            }

            $processedGroups[$nestedGroupDn] = true;
            $nestedGroups[] = $nestedGroupDn;

            $deeperNested = $this->fetchNestedGroups($nestedGroupDn, $connectionName, $processedGroups);
            $nestedGroups = array_merge($nestedGroups, $deeperNested);
        }

        return $nestedGroups;
    }

    /**
     * Verifiziert die Credentials per Bind gegen das Directory. Es wird kein
     * lokaler Hash geprüft oder gespeichert.
     */
    public function authenticate(ExternalUserSource $source, string $username, string $password): bool
    {
        try {
            return $this->authenticateAndFetch($source, $username, $password) !== null;
        } catch (LdapAuthenticationFailedException) {
            return false;
        }
    }

    /**
     * Verifiziert die Credentials per Bind und liefert bei Erfolg das auf
     * dieselbe Shape wie {@see fetchUsers()} normalisierte Nutzerprofil zurück –
     * inklusive des stabilen Identifiers (entryUUID/objectGUID).
     *
     * Dreizustand: null = Nutzer existiert nicht im Verzeichnis; Rückgabe-Array =
     * gefunden und erfolgreich authentifiziert; {@see LdapAuthenticationFailedException}
     * = gefunden, aber Passwort falsch (der Nutzer gehört zum IdP → kein lokaler
     * Fallback).
     *
     * @return array{identifier: string|null, email: string|null, first_name: string, last_name: string, groups: array<int, string>, email_verified: bool, meta_data: array<string, mixed>}|null
     * @throws LdapAuthenticationFailedException
     */
    public function authenticateAndFetch(ExternalUserSource $source, string $username, string $password): ?array
    {
        $connectionName = $this->registerConnection($source);

        $config = $source->config ?? [];
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        $user = $this->buildLoginQuery($connectionName, $config, $username)->first();

        if (!$user) {
            return null;
        }

        // Ein leeres Passwort führt bei vielen Servern zu einem "unauthenticated
        // bind", der fälschlich als Erfolg gewertet würde – hier explizit ablehnen.
        if ($password === '' || !$this->bindAs($config, $user->getDn(), $password)) {
            throw new LdapAuthenticationFailedException(
                'LDAP bind failed for a user that exists in the directory.'
            );
        }

        return [
            'identifier' => $this->getAttributeValue($user, $identifierAttribute),
            'email' => $this->getAttributeValue($user, 'mail'),
            'first_name' => $this->getAttributeValue($user, 'givenName') ?? '',
            'last_name' => $this->getAttributeValue($user, 'sn') ?? '',
            'groups' => $this->fetchUserGroupsFromLdapUser($user, $source, $connectionName),
            'email_verified' => true,
            'meta_data' => [
                'distinguished_name' => $user->getDn(),
                'sam_account_name' => $this->getAttributeValue($user, 'sAMAccountName'),
                'user_principal_name' => $this->getAttributeValue($user, 'userPrincipalName'),
                'display_name' => $this->getAttributeValue($user, 'displayName'),
            ],
        ];
    }

    /**
     * Baut die Login-Suche: derselbe user_filter wie beim Sync UND einer der drei
     * Login-Attribut-Vergleiche. Wer vom Filter ausgeschlossen ist, darf sich auch
     * nicht interaktiv anmelden. Die Attribut-Vergleiche müssen wegen des rawFilter
     * explizit als OR-Gruppe geklammert werden – LdapRecord wrappt where/orWhere
     * neben einem raw-Filter sonst nicht als einzelnes OR.
     *
     * @param array<string, mixed> $config
     */
    private function buildLoginQuery(
        string $connectionName,
        array $config,
        string $username
    ): \LdapRecord\Query\Model\Builder {
        // Operationale Attribute (z. B. entryUUID bei OpenLDAP) werden von '*'
        // nicht mitgeliefert – das Identifier-Attribut muss wie beim Sync
        // explizit selektiert werden, sonst ist der Login-Treffer identifier-los.
        $identifierAttribute = $config['identifier_attribute'] ?? 'objectGUID';

        $query = LdapUser::on($connectionName)
            ->select(['*', $identifierAttribute, 'mail', 'givenName', 'sn', 'displayName', 'cn'])
            ->rawFilter($this->normalizeFilter($config['user_filter'] ?? null))
            ->orFilter(static function ($query) use ($username): void {
                $query->where('sAMAccountName', '=', $username)
                    ->orWhere('userPrincipalName', '=', $username)
                    ->orWhere('mail', '=', $username);
            });

        $baseDn = $config['base_dn'] ?? '';

        if ($baseDn !== '') {
            $query->in($baseDn);
        }

        return $query;
    }

    /**
     * Führt einen Bind als der angegebene User-DN aus und liefert true bei Erfolg.
     *
     * @param array<string, mixed> $config
     */
    private function bindAs(array $config, string $userDn, string $password): bool
    {
        $userConnection = new Connection([
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
        ]);

        try {
            $userConnection->connect();

            return true;
        } catch (\Throwable $e) {
            // Falsche Credentials / Bind-Fehler → kein Login, aber kein Abbruch.
            return false;
        }
    }

    private function getAttributeValue(LdapUser $ldapUser, string $attribute): ?string
    {
        $value = $ldapUser->getAttribute($attribute);

        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return $value;
    }
}
