<?php

namespace Tests\Unit\Modules\Availability\Services;

use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AvailabilitySeriesServiceTest extends TestCase
{
    private AvailabilitySeriesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilitySeriesService::class);
    }

    #[Test]
    public function create_persists_series_with_given_frequency_and_until(): void
    {
        $series = $this->service->create('weekly', '2025-12-31');

        $this->assertInstanceOf(AvailabilitySeries::class, $series);
        $this->assertTrue($series->exists);
        $this->assertSame('weekly', $series->frequency);
        $this->assertDatabaseHas('availability_series', [
            'id' => $series->id,
            'frequency' => 'weekly',
        ]);
    }

    #[Test]
    public function delete_series_removes_series(): void
    {
        $series = AvailabilitySeries::factory()->create();

        $this->service->deleteSeries($series);

        $this->assertDatabaseMissing('availability_series', ['id' => $series->id]);
    }
}
