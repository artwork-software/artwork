<?php

namespace Tests\Unit\Modules\Sage100\Services;

use Artwork\Modules\Sage100\Services\Sage100Service;
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
}
