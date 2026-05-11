<?php

namespace Tests\Unit\Modules\ServiceProvider\Services;

use Artwork\Modules\ServiceProvider\Services\ServiceProviderContactsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ServiceProviderContactsServiceTest extends TestCase
{
    private ServiceProviderContactsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ServiceProviderContactsService::class);
    }

    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $this->assertInstanceOf(ServiceProviderContactsService::class, $this->service);
    }
}
