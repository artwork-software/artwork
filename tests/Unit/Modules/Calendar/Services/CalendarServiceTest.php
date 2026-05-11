<?php

namespace Tests\Unit\Modules\Calendar\Services;

use Artwork\Modules\Calendar\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CalendarServiceTest extends TestCase
{
    private CalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CalendarService::class);
    }

    #[Test]
    public function create_vacation_and_availability_period_calendar_returns_weeks(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-15'));

        $result = $this->service->createVacationAndAvailabilityPeriodCalendar();

        $this->assertInstanceOf(Collection::class, $result);
        // Each chunk should have 7 days
        foreach ($result as $week) {
            $this->assertCount(7, $week);
        }
    }

    #[Test]
    public function create_vacation_and_availability_period_calendar_accepts_month_param(): void
    {
        $result = $this->service->createVacationAndAvailabilityPeriodCalendar('2025-02-15');

        $this->assertInstanceOf(Collection::class, $result);
        // Each chunk should have 7 days
        $first = $result->first();
        $this->assertCount(7, $first);
        // The first row should include some days from January
        $this->assertSame('2025-01-27', $first->first()['date']);
    }
}
