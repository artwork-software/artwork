<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Services\ShiftListViewService;
use Artwork\Modules\User\Models\UserShiftListViewSettings;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regression: Termine verschwanden in der Listenansicht, weil die Termin-Query
 * gegen Datetime-Spalten lief, der Zeitraum-Filter aber Mitternachts-Carbons
 * lieferte — damit fielen alle Termine des letzten Zeitraumtags raus
 * (bei 1-Tages-Zeiträumen: alle Termine des Tages).
 */
final class ShiftListViewEventRangeTest extends FeatureTestCase
{
    private function settings(): UserShiftListViewSettings
    {
        return new UserShiftListViewSettings([
            'show_appointments' => true,
            'show_fully_staffed_shifts' => true,
        ]);
    }

    private function eventsForDay(array $groupedShifts, string $day): array
    {
        foreach ($groupedShifts as $dayGroup) {
            if ($dayGroup['day'] === $day) {
                return array_merge(...array_map(
                    static fn (array $room): array => $room['events'],
                    $dayGroup['rooms'],
                ) ?: [[]]);
            }
        }

        return [];
    }

    #[Test]
    public function single_day_range_includes_events_of_that_day(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2026-08-30 18:00:00',
            'end_time' => '2026-08-30 23:00:00',
        ]);

        $grouped = app(ShiftListViewService::class)->getGroupedShifts(
            Carbon::parse('2026-08-30'),
            Carbon::parse('2026-08-30'),
            $this->settings(),
        );

        $events = $this->eventsForDay($grouped, '2026-08-30');
        $this->assertCount(1, $events);
        $this->assertSame($event->id, $events[0]['id']);
    }

    #[Test]
    public function events_on_last_day_of_range_are_included(): void
    {
        Event::factory()->create([
            'start_time' => '2026-08-30 15:00:00',
            'end_time' => '2026-08-30 23:00:00',
        ]);

        $grouped = app(ShiftListViewService::class)->getGroupedShifts(
            Carbon::parse('2026-08-24'),
            Carbon::parse('2026-08-30'),
            $this->settings(),
        );

        $this->assertCount(1, $this->eventsForDay($grouped, '2026-08-30'));
    }

    #[Test]
    public function events_outside_range_stay_excluded(): void
    {
        Event::factory()->create([
            'start_time' => '2026-08-31 10:00:00',
            'end_time' => '2026-08-31 12:00:00',
        ]);

        $grouped = app(ShiftListViewService::class)->getGroupedShifts(
            Carbon::parse('2026-08-24'),
            Carbon::parse('2026-08-30'),
            $this->settings(),
        );

        $this->assertSame([], $this->eventsForDay($grouped, '2026-08-31'));
        foreach ($grouped as $dayGroup) {
            foreach ($dayGroup['rooms'] as $room) {
                $this->assertSame([], $room['events']);
            }
        }
    }
}
