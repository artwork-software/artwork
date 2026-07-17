<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Api\LdapApi;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class LdapApiFilterTest extends TestCase
{
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

    private function normalizeFilter(?string $filter): string
    {
        $method = new ReflectionMethod(LdapApi::class, 'normalizeFilter');

        return $method->invoke(new LdapApi(), $filter);
    }
}
