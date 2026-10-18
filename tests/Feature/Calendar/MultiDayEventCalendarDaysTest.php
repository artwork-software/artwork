<?php

namespace Tests\Feature\Calendar;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Mehrtägige Termine müssen nach einem Reload an jedem belegten Kalendertag erscheinen,
 * auch wenn die Enduhrzeit vor der Startuhrzeit liegt.
 */
final class MultiDayEventCalendarDaysTest extends TestCase
{
    #[Test]
    public function event_appears_on_its_last_day_when_it_ends_earlier_than_it_started(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => false,
            'start_time' => '2026-10-24 15:00:00',
            'end_time' => '2026-10-26 14:00:00',
        ]);

        $this->assertSame(
            ['24.10.2026', '25.10.2026', '26.10.2026'],
            $this->daysContainingEvent($room, $event),
        );
    }

    #[Test]
    public function event_ending_at_midnight_does_not_appear_on_the_following_day(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => false,
            'start_time' => '2026-10-24 22:00:00',
            'end_time' => '2026-10-25 00:00:00',
        ]);

        $this->assertSame(['24.10.2026'], $this->daysContainingEvent($room, $event));
    }

    /**
     * @return array<int, string>
     */
    private function daysContainingEvent(Room $room, Event $event): array
    {
        $response = $this->getJson(route('events.all', [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-31',
            'isPlanning' => 'false',
        ]))->assertOk();

        $days = [];
        foreach ($response->json('calendar') as $roomData) {
            if ((int) $roomData['roomId'] !== $room->id) {
                continue;
            }
            foreach ($roomData['content'] as $day => $content) {
                if (collect($content['events'] ?? [])->contains('id', $event->id)) {
                    $days[] = $day;
                }
            }
        }

        return $days;
    }
}
