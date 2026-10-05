<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Shift\Services\ShiftListViewService;
use Artwork\Modules\User\Models\UserShiftListViewSettings;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Feiertage in der Schichtplan-Listenansicht: jährliche Einträge stehen unter dem Datum des
 * angezeigten Jahres, mehrtägige Einträge (z. B. Ferien) an jedem ihrer Tage – nicht nur am Starttag.
 */
final class ShiftListViewHolidayTest extends FeatureTestCase
{
    #[Test]
    public function yearly_holiday_appears_in_the_displayed_year(): void
    {
        $this->holiday('Spielzeiteröffnung', '2024-09-05', '2024-09-05', true);
        $this->eventOn('2026-09-05');

        $this->assertSame(['Spielzeiteröffnung'], $this->holidayNamesFor('2026-09-01', '2026-09-10')['2026-09-05']);
    }

    #[Test]
    public function multi_day_holiday_appears_on_every_day(): void
    {
        $this->holiday('Herbstferien', '2026-10-05', '2026-10-09', false);
        $this->eventOn('2026-10-05');
        $this->eventOn('2026-10-07');

        $names = $this->holidayNamesFor('2026-10-06', '2026-10-08');

        $this->assertSame(['Herbstferien'], $names['2026-10-07']);
        $this->assertArrayNotHasKey('2026-10-05', $names);
    }

    private function holiday(string $name, string $date, string $endDate, bool $yearly): void
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

    private function eventOn(string $day): void
    {
        Event::factory()->create([
            'start_time' => $day . ' 18:00:00',
            'end_time' => $day . ' 20:00:00',
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function holidayNamesFor(string $start, string $end): array
    {
        $grouped = app(ShiftListViewService::class)->getGroupedShifts(
            Carbon::parse($start),
            Carbon::parse($end),
            new UserShiftListViewSettings(['show_appointments' => true, 'show_fully_staffed_shifts' => true]),
        );

        $names = [];
        foreach ($grouped as $dayGroup) {
            $names[$dayGroup['day']] = array_map(
                static fn ($holiday) => is_array($holiday) ? $holiday['name'] : $holiday->name,
                $dayGroup['holidays'],
            );
        }

        return $names;
    }
}
