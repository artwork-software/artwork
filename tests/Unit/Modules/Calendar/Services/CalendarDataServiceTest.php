<?php

namespace Tests\Unit\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarPeriodDTO;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\User\Models\User;
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
        $today = Carbon::parse('2025-06-15 14:30:00');

        [$start, $end] = $this->service->getProjectDateRange(null, $today);

        // Ganzer Tag, nicht ein Zeitpunkt – und das übergebene Datum bleibt unverändert
        $this->assertSame('2025-06-15 00:00:00', $start->toDateTimeString());
        $this->assertSame('2025-06-15 23:59:59', $end->toDateTimeString());
        $this->assertSame('2025-06-15 14:30:00', $today->toDateTimeString());
    }

    #[Test]
    public function get_project_date_range_covers_today_for_a_project_without_events(): void
    {
        $project = \Artwork\Modules\Project\Models\Project::factory()->create();

        [$start, $end] = $this->service->getProjectDateRange($project, Carbon::parse('2025-06-15 14:30:00'));

        $this->assertSame('2025-06-15 00:00:00', $start->toDateTimeString());
        $this->assertSame('2025-06-15 23:59:59', $end->toDateTimeString());
    }

    #[Test]
    public function yearly_holiday_appears_under_the_date_of_the_displayed_year(): void
    {
        $this->createHoliday('Spielzeiteröffnung', '2024-09-05', '2024-09-05', true);

        $holidaysByDate = $this->holidayNamesByDate('2026-09-01', '2026-09-10');

        $this->assertSame(['Spielzeiteröffnung'], $holidaysByDate['2026-09-05']);
        $this->assertSame([], $holidaysByDate['2026-09-04']);
    }

    #[Test]
    public function yearly_holiday_is_found_across_the_turn_of_the_year(): void
    {
        $this->createHoliday('Betriebsferien', '2024-12-30', '2025-01-02', true);

        $holidaysByDate = $this->holidayNamesByDate('2026-12-28', '2027-01-05');

        foreach (['2026-12-30', '2026-12-31', '2027-01-01', '2027-01-02'] as $day) {
            $this->assertSame(['Betriebsferien'], $holidaysByDate[$day], $day);
        }
        $this->assertSame([], $holidaysByDate['2026-12-29']);
        $this->assertSame([], $holidaysByDate['2027-01-03']);
    }

    #[Test]
    public function yearly_leap_day_is_skipped_in_non_leap_years(): void
    {
        $this->createHoliday('Schalttag', '2024-02-29', '2024-02-29', true);

        $this->assertSame([], $this->holidayNamesByDate('2027-02-27', '2027-03-02')['2027-03-01']);
        $this->assertSame(['Schalttag'], $this->holidayNamesByDate('2028-02-27', '2028-03-02')['2028-02-29']);
    }

    #[Test]
    public function fixed_holiday_only_appears_in_its_own_year(): void
    {
        $this->createHoliday('Einmalig', '2026-03-10', '2026-03-11', false);

        $this->assertSame(['Einmalig'], $this->holidayNamesByDate('2026-03-09', '2026-03-12')['2026-03-11']);
        $this->assertSame([], $this->holidayNamesByDate('2027-03-09', '2027-03-12')['2027-03-10']);
    }

    #[Test]
    public function holiday_without_end_date_is_shown_as_single_day(): void
    {
        // end_date ist im HolidayRequest optional; vorher warf der Kalender dann einen 500er
        $this->createHoliday('Ohne Ende', '2026-05-04', null, false);
        $this->createHoliday('Jährlich ohne Ende', '2020-05-06', null, true);

        $holidaysByDate = $this->holidayNamesByDate('2026-05-03', '2026-05-07');

        $this->assertSame(['Ohne Ende'], $holidaysByDate['2026-05-04']);
        $this->assertSame(['Jährlich ohne Ende'], $holidaysByDate['2026-05-06']);
        $this->assertSame([], $holidaysByDate['2026-05-05']);
    }

    private function createHoliday(string $name, string $date, ?string $endDate, bool $yearly): void
    {
        Holiday::create([
            'name' => $name,
            'date' => $date,
            'end_date' => $endDate,
            'yearly' => $yearly,
            'from_api' => false,
            'treatAsSpecialDay' => false,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function holidayNamesByDate(string $start, string $end): array
    {
        $periods = $this->service->createCalendarPeriodDto(
            Carbon::parse($start),
            Carbon::parse($end),
            User::factory()->create(),
            false,
            false
        );

        return collect($periods)
            ->filter(fn ($period) => $period instanceof CalendarPeriodDTO && !$period->isExtraRow)
            ->mapWithKeys(fn (CalendarPeriodDTO $period) => [
                $period->date => collect($period->holidays)->pluck('name')->all(),
            ])
            ->all();
    }
}
