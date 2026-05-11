<?php

namespace Tests\Unit\Modules\ServiceProvider\Services;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ServiceProviderServiceTest extends TestCase
{
    private ServiceProviderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ServiceProviderService::class);
    }

    #[Test]
    public function get_shifts_with_event_ordered_by_start_returns_collection(): void
    {
        $provider = ServiceProvider::factory()->create();

        $shifts = $this->service->getShiftsWithEventOrderedByStartAscending($provider);

        $this->assertInstanceOf(Collection::class, $shifts);
        $this->assertCount(0, $shifts);
    }

    #[Test]
    public function search_service_providers_returns_support_collection(): void
    {
        ServiceProvider::factory()->create(['provider_name' => 'Acme GmbH']);

        $result = $this->service->searchServiceProviders('Acme');

        // With scout null driver, an empty collection is expected.
        $this->assertInstanceOf(SupportCollection::class, $result);
    }
}
