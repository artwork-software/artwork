<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Api\LdapApi;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use LdapRecord\Models\Entry;
use LdapRecord\Query\Model\Builder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * Deckt die Gruppenauflösung ab, die ausschliesslich beim echten Sync laeuft und
 * deshalb vom Verbindungstest nie beruehrt wurde – dort hat der Aufruf einer nicht
 * existierenden LdapRecord-Methode (findByDn) jeden Sync-Lauf abgebrochen.
 */
final class LdapNestedGroupResolutionTest extends TestCase
{
    private const string ARTWORK = 'CN=SG-Artwork,OU=Security Groups,DC=hau,DC=local';

    private const string STAFF = 'CN=SG-Staff,OU=Security Groups,DC=hau,DC=local';

    private const string ALL = 'CN=SG-All,OU=Security Groups,DC=hau,DC=local';

    /** @var array<int, Throwable> */
    private array $reported = [];

    protected function tearDown(): void
    {
        Container::setInstance(null);
        $this->reported = [];

        parent::tearDown();
    }

    /**
     * report() braucht einen Container mit ExceptionHandler – hier ein Stub, der die
     * gemeldeten Exceptions mitschreibt, damit der Test belegen kann, dass der Fehler
     * nicht still verschluckt wird.
     */
    private function fakeExceptionHandler(): void
    {
        $container = new Container();
        Container::setInstance($container);

        $reported = &$this->reported;

        $container->instance(ExceptionHandler::class, new class ($reported) implements ExceptionHandler {
            /** @param array<int, Throwable> $reported */
            public function __construct(private array &$reported)
            {
            }

            public function report(Throwable $e): void
            {
                $this->reported[] = $e;
            }

            public function shouldReport(Throwable $e): bool
            {
                return true;
            }

            public function render($request, Throwable $e): void
            {
            }

            public function renderForConsole($output, Throwable $e): void
            {
            }
        });
    }

    #[Test]
    public function theDnLookupUsesAMethodThatActuallyExistsOnTheLdapRecordBuilder(): void
    {
        // Regressionsschutz: findByDn() gab es in LdapRecord v3 nie, der Aufruf
        // flog erst zur Laufzeit als BadMethodCallException auf.
        $this->assertTrue(method_exists(Builder::class, 'find'));
        $this->assertFalse(method_exists(Builder::class, 'findByDn'));
    }

    #[Test]
    public function nestedGroupsAreResolvedRecursively(): void
    {
        $api = $this->apiWithGroupTree([
            self::ARTWORK => [self::STAFF],
            self::STAFF => [self::ALL],
            self::ALL => [],
        ]);

        $groups = $this->resolve($api, [self::ARTWORK]);

        $this->assertEqualsCanonicalizing([self::ARTWORK, self::STAFF, self::ALL], $groups);
    }

    #[Test]
    public function eachGroupIsLookedUpOnlyOnce(): void
    {
        $api = $this->apiWithGroupTree([
            self::ARTWORK => [self::ALL],
            self::STAFF => [self::ALL],
            self::ALL => [],
        ]);

        $this->resolve($api, [self::ARTWORK, self::STAFF]);

        // SG-All haengt an beiden direkten Gruppen – der Cache muss den zweiten
        // Verzeichnis-Roundtrip einsparen.
        $this->assertSame(1, $api->lookups[self::ALL]);
    }

    #[Test]
    public function aCycleInTheGroupTreeTerminates(): void
    {
        $api = $this->apiWithGroupTree([
            self::ARTWORK => [self::STAFF],
            self::STAFF => [self::ALL],
            self::ALL => [self::ARTWORK],
        ]);

        $groups = $this->resolve($api, [self::ARTWORK]);

        $this->assertEqualsCanonicalizing([self::ARTWORK, self::STAFF, self::ALL], $groups);
    }

    #[Test]
    public function anUnreadableGroupIsSkippedInsteadOfAbortingTheRun(): void
    {
        // Eine Gruppe aus einer fremden Domaene oder ohne Leserecht darf nicht den
        // kompletten Sync-Job mitreissen.
        $this->fakeExceptionHandler();

        $api = $this->apiWithGroupTree([
            self::ARTWORK => [self::STAFF],
            self::STAFF => null, // Lookup wirft
            self::ALL => [],
        ]);

        $groups = $this->resolve($api, [self::ARTWORK, self::ALL]);

        $this->assertEqualsCanonicalizing([self::ARTWORK, self::STAFF, self::ALL], $groups);
        $this->assertCount(1, $this->reported);
        $this->assertInstanceOf(RuntimeException::class, $this->reported[0]);
    }

    #[Test]
    public function aSingleValuedMemberOfAttributeDoesNotFatal(): void
    {
        $api = $this->apiWithGroupTree([self::ARTWORK => []]);

        $groups = $this->resolve($api, self::ARTWORK);

        $this->assertSame([self::ARTWORK], array_values($groups));
    }

    /**
     * @param array<string, array<int, string>|null> $tree  DN => Eltern-Gruppen, null = Lookup wirft
     */
    private function apiWithGroupTree(array $tree): LdapApi
    {
        return new class ($tree) extends LdapApi {
            /** @var array<string, int> */
            public array $lookups = [];

            /** @param array<string, array<int, string>|null> $tree */
            public function __construct(private readonly array $tree)
            {
            }

            protected function findGroupByDn(string $connectionName, string $groupDn): ?Entry
            {
                $this->lookups[$groupDn] = ($this->lookups[$groupDn] ?? 0) + 1;

                if (!array_key_exists($groupDn, $this->tree)) {
                    return null;
                }

                if ($this->tree[$groupDn] === null) {
                    throw new RuntimeException('Group is not readable.');
                }

                return new Entry(['memberof' => $this->tree[$groupDn]]);
            }
        };
    }

    /** @param array<int, string>|string $memberOf */
    private function resolve(LdapApi $api, array|string $memberOf): array
    {
        $method = new ReflectionMethod(LdapApi::class, 'fetchUserGroupsFromLdapUser');

        return $method->invoke(
            $api,
            new Entry(['memberof' => $memberOf]),
            new ExternalUserSource(),
            'test-connection',
            true
        );
    }
}
