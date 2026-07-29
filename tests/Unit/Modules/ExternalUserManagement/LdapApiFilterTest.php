<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Api\LdapApi;
use LdapRecord\Connection;
use LdapRecord\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class LdapApiFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::getInstance()->flush();

        parent::tearDown();
    }

    #[Test]
    public function theDefaultUserFilterIsPortableAcrossLdapImplementations(): void
    {
        $this->assertSame('(objectClass=person)', $this->normalizeFilter(null));
    }

    #[Test]
    public function customFiltersAreTrimmedAndWrappedWhenNecessary(): void
    {
        $this->assertSame('(uid=*)', $this->normalizeFilter(' uid=* '));
        $this->assertSame(
            '(&(objectClass=person)(mail=*))',
            $this->normalizeFilter('(&(objectClass=person)(mail=*))')
        );
    }

    #[Test]
    public function theLoginQueryEnforcesTheUserFilterNextToTheLoginAttributes(): void
    {
        $filter = $this->compiledLoginFilter([
            'user_filter' => '(&(objectClass=user)(memberOf=CN=Artwork,DC=example,DC=com))',
        ]);

        // user_filter UND die OR-Gruppe der Login-Attribute – wer vom Filter
        // ausgeschlossen ist, darf sich auch nicht interaktiv anmelden.
        $this->assertStringStartsWith('(&(&(objectClass=user)(memberOf=', $filter);
        $this->assertStringContainsString('(|', $filter);
        $this->assertStringContainsString('(sAMAccountName=', $filter);
        $this->assertStringContainsString('(userPrincipalName=', $filter);
        $this->assertStringContainsString('(mail=', $filter);
    }

    #[Test]
    public function theLoginQueryFallsBackToThePortableDefaultFilter(): void
    {
        $this->assertStringStartsWith(
            '(&(objectClass=person)(|',
            $this->compiledLoginFilter([])
        );
    }

    #[Test]
    public function theLoginQuerySelectsTheConfiguredIdentifierAttribute(): void
    {
        // Operationale Attribute wie entryUUID (OpenLDAP) liefert '*' nicht mit –
        // ohne explizites Select wäre der Login-Treffer identifier-los und der
        // Credential-Login fiele fälschlich auf das lokale Passwort zurück.
        $selects = $this->loginQuery(['identifier_attribute' => 'entryUUID'])->getSelects();

        $this->assertContains('entryUUID', $selects);
        $this->assertContains('*', $selects);
        $this->assertContains('objectGUID', $this->loginQuery([])->getSelects());
    }

    private function loginQuery(array $config): \LdapRecord\Query\Model\Builder
    {
        $connectionName = 'ldap-api-filter-test';
        Container::getInstance()->addConnection(
            new Connection(['hosts' => ['localhost']]),
            $connectionName
        );

        $method = new ReflectionMethod(LdapApi::class, 'buildLoginQuery');

        return $method->invoke(new LdapApi(), $connectionName, $config, 'jane@example.com');
    }

    private function compiledLoginFilter(array $config): string
    {
        return $this->loginQuery($config)->getQuery();
    }

    private function normalizeFilter(?string $filter): string
    {
        $method = new ReflectionMethod(LdapApi::class, 'normalizeFilter');

        return $method->invoke(new LdapApi(), $filter);
    }
}
