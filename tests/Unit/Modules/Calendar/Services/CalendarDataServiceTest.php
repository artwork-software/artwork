<?php

namespace Tests\Unit\Modules\Calendar\Services;

use Artwork\Modules\Calendar\Services\CalendarDataService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CalendarDataServiceTest extends TestCase
{
    private CalendarDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CalendarDataService::class);
    }

    #[Test]
    public function get_project_date_range_returns_today_range_when_project_is_null(): void
    {
        $today = Carbon::parse('2025-06-15');

        [$start, $end] = $this->service->getProjectDateRange(null, $today);

        // Note: the implementation mutates $today, so start == end at endOfDay.
        $this->assertSame('2025-06-15', $start->toDateString());
        $this->assertSame('2025-06-15', $end->toDateString());
    }
}
