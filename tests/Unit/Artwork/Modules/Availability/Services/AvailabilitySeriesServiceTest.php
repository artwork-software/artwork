<?php

namespace Tests\Unit\Artwork\Modules\Availability\Services;


use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use Artwork\Modules\Availability\Repositories\AvailabilitySeriesRepository;
use Tests\TestCase;

class AvailabilitySeriesServiceTest extends TestCase
{
    private AvailabilitySeriesRepository $availabilitySeriesRepository;
    private AvailabilitySeriesService $availabilitySeriesService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availabilitySeriesRepository = $this->createMock(AvailabilitySeriesRepository::class);
        $this->availabilitySeriesService = new AvailabilitySeriesService($this->availabilitySeriesRepository);
    }

    public function testCreateMethod(): void
    {
        $frequency = 'weekly';
        $until = '2023-01-31';

        $availabilitySeries = new AvailabilitySeries();
        $availabilitySeries->frequency = $frequency;
        $availabilitySeries->end_date = $until;

        $this->availabilitySeriesRepository->expects($this->once())
            ->method('save')
            ->with($availabilitySeries)
            ->willReturn($availabilitySeries);

        $result = $this->availabilitySeriesService->create($frequency, $until);

        $this->assertInstanceOf(AvailabilitySeries::class, $result);
        $this->assertEquals($frequency, $result->frequency);
        $this->assertEquals($until, $result->end_date);
    }

    public function testDeleteSeriesMethod(): void
    {
        $availabilitySeries = AvailabilitySeries::factory()->create();
        Availability::factory(4)->create(['series_id' => $availabilitySeries->id]);
        $this->assertDatabaseHas('availabilities', ['series_id' => $availabilitySeries->id]);
        $this->assertEquals(4, Availability::where('series_id', $availabilitySeries->id)->count());
        $this->availabilitySeriesService->deleteSeries($availabilitySeries);
        $this->assertDatabaseMissing('availabilities', ['series_id' => $availabilitySeries->id]);
    }
}
