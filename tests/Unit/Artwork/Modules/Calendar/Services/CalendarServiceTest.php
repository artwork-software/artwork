<?php

namespace Tests\Unit\Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\Services\CalendarService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    /**
     * @var CalendarService
     */
    protected CalendarService $calendarService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calendarService = new CalendarService();
    }

    public function testCreateVacationAndAvailabilityPeriodCalendar(): void
    {
        $result = $this->calendarService->createVacationAndAvailabilityPeriodCalendar();

        $result->each(function (Collection $week): void {
            $this->assertCount(7, $week);

            $week->each(function (array $day): void {
                $this->assertArrayHasKey('date', $day);
                $this->assertArrayHasKey('day', $day);
                $this->assertArrayHasKey('inMonth', $day);
                $this->assertArrayHasKey('isToday', $day);
            });
        });
    }
}
