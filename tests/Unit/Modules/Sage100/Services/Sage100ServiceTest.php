<?php

namespace Tests\Unit\Modules\Sage100\Services;

use Artwork\Modules\Sage100\Clients\SageClientFactory;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Illuminate\Contracts\Encryption\DecryptException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sage100Service makes external HTTP calls to a Sage 100 API. All public methods that
 * trigger network requests (importDataToBudget, etc.) are intentionally not tested here.
 * This test only verifies that the service can be constructed and resolved by the
 * container so refactors of its constructor signature are caught early.
 */
final class Sage100ServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(Sage100Service::class);

        $this->assertInstanceOf(Sage100Service::class, $service);
    }

    #[Test]
    public function resolving_the_service_does_not_build_the_sage_client(): void
    {
        // Ein nicht entschlüsselbares Passwort darf nur den Import treffen, nicht jeden Controller mit dem Service
        $factory = $this->createMock(SageClientFactory::class);
        $factory->expects($this->never())->method('createClient');
        $factory->method('createClient')->willThrowException(new DecryptException('The payload is invalid.'));
        $this->app->instance(SageClientFactory::class, $factory);

        $this->assertInstanceOf(Sage100Service::class, app(Sage100Service::class));
    }
}
