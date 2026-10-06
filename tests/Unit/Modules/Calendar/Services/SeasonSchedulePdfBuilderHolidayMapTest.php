<?php

namespace Tests\Unit\Modules\Calendar\Services;

use Artwork\Modules\Calendar\Services\SeasonSchedulePdfBuilder;
use Artwork\Modules\Holidays\Models\Holiday;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

final class SeasonSchedulePdfBuilderHolidayMapTest extends TestCase
{
    #[Test]
    public function yearly_holiday_block_over_new_year_appears_in_january_of_the_period(): void
    {
        // Angelegt 2020 als 30.12.–02.01.: im Januar 2027 stammt der Block aus dem Vorjahr (30.12.2026)
        Holiday::create([
            'name' => 'Betriebsferien',
            'date' => '2020-12-30',
            'end_date' => '2021-01-02',
            'yearly' => true,
            'from_api' => false,
            'treatAsSpecialDay' => false,
        ]);

        $map = $this->holidayMap(Carbon::parse('2027-01-01'), Carbon::parse('2027-01-31'));

        $this->assertSame('Betriebsferien', $map['2027-01-01'] ?? null);
        $this->assertSame('Betriebsferien', $map['2027-01-02'] ?? null);
        $this->assertArrayNotHasKey('2027-01-03', $map);
        $this->assertArrayNotHasKey('2026-12-31', $map);
    }

    /**
     * @return array<string, string>
     */
    private function holidayMap(Carbon $start, Carbon $end): array
    {
        $method = new ReflectionMethod(SeasonSchedulePdfBuilder::class, 'buildHolidayMap');

        return $method->invoke(app(SeasonSchedulePdfBuilder::class), $start, $end);
    }
}
