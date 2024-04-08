<?php

namespace Tests\Unit\Artwork\Modules\Vacation\Services;

use Artwork\Modules\Vacation\Services\VacationSeriesService;
use Artwork\Modules\Vacation\Models\VacationSeries;
use Tests\TestCase;

class VacationSeriesServiceTest extends TestCase
{
    protected VacationSeriesService $vacationSeriesService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vacationSeriesService = app(VacationSeriesService::class);
    }

    public function testCreate(): void
    {
        $frequency = 'weekly';
        $until = '2022-12-31';

        $vacationSeries = $this->vacationSeriesService->create($frequency, $until);

        $this->assertInstanceOf(VacationSeries::class, $vacationSeries);
        $this->assertEquals($frequency, $vacationSeries->frequency);
        $this->assertEquals($until, $vacationSeries->end_date);
    }

    public function testDeleteSeries(): void
    {
        $vacationSeries = VacationSeries::factory()->create();

        $this->vacationSeriesService->deleteSeries($vacationSeries);

        $this->assertDatabaseMissing('vacation_series', ['id' => $vacationSeries->id]);
    }
}
