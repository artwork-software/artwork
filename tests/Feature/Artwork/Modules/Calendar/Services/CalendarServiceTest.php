<?php

namespace Tests\Feature\Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Project\Services\ProjectStateService;
use Artwork\Modules\Shift\Services\ShiftService;
use Illuminate\Cache\CacheManager;
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

        $this->calendarService = new CalendarService(
            app()->get(CacheManager::class),
            app()->get(ShiftService::class),
            app()->get(ProjectStateService::class),
        );
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
