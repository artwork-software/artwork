<?php

namespace Tests\Unit\Modules\Vacation\Services;

use Artwork\Modules\Vacation\Models\VacationSeries;
use Artwork\Modules\Vacation\Services\VacationSeriesService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VacationSeriesServiceTest extends TestCase
{
    private VacationSeriesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VacationSeriesService::class);
    }

    #[Test]
    public function create_persists_vacation_series(): void
    {
        $series = $this->service->create('weekly', '2025-12-31');

        $this->assertInstanceOf(VacationSeries::class, $series);
        $this->assertTrue($series->exists);
        $this->assertSame('weekly', $series->frequency);
    }

    #[Test]
    public function delete_series_removes_series(): void
    {
        $series = VacationSeries::factory()->create();

        $this->service->deleteSeries($series);

        $this->assertDatabaseMissing('vacation_series', ['id' => $series->id]);
    }
}
