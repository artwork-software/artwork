<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Zeiten bei Mehrfachbearbeitung (Kalender) und Termin-Bulk (Projekt): nie Ende vor Start,
 * Termine über Mitternacht und mehrtägige Termine behalten ihre Dauer.
 */
final class EventMultiEditTimesTest extends FeatureTestCase
{
    #[Test]
    public function multi_edit_to_fixed_date_keeps_overnight_duration(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 20:00:00', '2026-08-02 02:00:00');

        $this->patchJson(route('multi-edit.save'), $this->multiEditPayload([$event->id], ['date' => '2026-09-10']))
            ->assertSuccessful();

        $this->assertTimes($event, '2026-09-10 20:00:00', '2026-09-11 02:00:00');
    }

    #[Test]
    public function multi_edit_to_fixed_date_keeps_multi_day_events(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-07 10:00:00', '2026-08-09 18:00:00');

        $this->patchJson(route('multi-edit.save'), $this->multiEditPayload([$event->id], ['date' => '2026-09-04']))
            ->assertSuccessful();

        $this->assertTimes($event, '2026-09-04 10:00:00', '2026-09-06 18:00:00');
    }

    /**
     * @return array<string, array{int, int, int, string, string}>
     */
    public static function offsets(): array
    {
        return [
            'plus 3 Stunden' => [1, 1, 3, '2026-08-01 13:00:00', '2026-08-01 15:00:00'],
            'minus 2 Tage' => [2, 2, 2, '2026-07-30 10:00:00', '2026-07-30 12:00:00'],
            'plus 1 Woche' => [1, 3, 1, '2026-08-08 10:00:00', '2026-08-08 12:00:00'],
            'minus 1 Monat' => [2, 4, 1, '2026-07-01 10:00:00', '2026-07-01 12:00:00'],
            'plus 1 Jahr' => [1, 5, 1, '2027-08-01 10:00:00', '2027-08-01 12:00:00'],
        ];
    }

    #[Test]
    #[DataProvider('offsets')]
    public function multi_edit_offsets_move_start_and_end(
        int $calculationType,
        int $type,
        int $value,
        string $expectedStart,
        string $expectedEnd
    ): void {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 10:00:00', '2026-08-01 12:00:00');

        $this->patchJson(route('multi-edit.save'), $this->multiEditPayload([$event->id], [
            'calculationType' => $calculationType,
            'type' => $type,
            'value' => $value,
        ]))->assertSuccessful();

        $this->assertTimes($event, $expectedStart, $expectedEnd);
    }

    #[Test]
    public function multi_edit_rejects_invalid_date(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 10:00:00', '2026-08-01 12:00:00');

        $this->patchJson(route('multi-edit.save'), $this->multiEditPayload([$event->id], ['date' => 'kein-datum']))
            ->assertUnprocessable();

        $this->assertTimes($event, '2026-08-01 10:00:00', '2026-08-01 12:00:00');
    }

    #[Test]
    public function multi_duplicate_to_fixed_date_keeps_overnight_duration_and_original(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 20:00:00', '2026-08-02 02:00:00');

        $this->patchJson(route('multi-duplicate.save'), $this->multiEditPayload([$event->id], ['date' => '2026-09-10']))
            ->assertSuccessful();

        $this->assertTimes($event, '2026-08-01 20:00:00', '2026-08-02 02:00:00');
        $this->assertDatabaseHas('events', [
            'room_id' => $event->room_id,
            'start_time' => '2026-09-10 20:00:00',
            'end_time' => '2026-09-11 02:00:00',
        ]);
    }

    #[Test]
    public function multi_duplicate_rolls_back_all_copies_on_invalid_input(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 10:00:00', '2026-08-01 12:00:00');
        $countBefore = Event::query()->count();

        $this->patchJson(route('multi-duplicate.save'), $this->multiEditPayload([$event->id], ['type' => 9]))
            ->assertUnprocessable();

        $this->assertSame($countBefore, Event::query()->count());
    }

    #[Test]
    public function bulk_edit_time_over_midnight_ends_on_next_day(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 10:00:00', '2026-08-01 12:00:00');

        $this->bulkEdit($event, ['selectedStartTime' => '23:45', 'selectedEndTime' => '0:15']);

        $this->assertTimes($event, '2026-08-01 23:45:00', '2026-08-02 00:15:00');
    }

    #[Test]
    public function bulk_edit_time_keeps_days_of_multi_day_event(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-07 10:00:00', '2026-08-09 18:00:00');

        $this->bulkEdit($event, ['selectedStartTime' => '09:00', 'selectedEndTime' => '17:00']);

        $this->assertTimes($event, '2026-08-07 09:00:00', '2026-08-09 17:00:00');
    }

    #[Test]
    public function bulk_edit_time_keeps_overnight_event_short(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 20:00:00', '2026-08-02 02:00:00');

        $this->bulkEdit($event, ['selectedStartTime' => '10:00', 'selectedEndTime' => '12:00']);

        $this->assertTimes($event, '2026-08-01 10:00:00', '2026-08-01 12:00:00');
    }

    #[Test]
    public function bulk_edit_only_start_after_end_keeps_duration(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 10:00:00', '2026-08-01 12:00:00');

        $this->bulkEdit($event, ['selectedStartTime' => '13:00']);

        $this->assertTimes($event, '2026-08-01 13:00:00', '2026-08-01 15:00:00');
    }

    #[Test]
    public function bulk_edit_day_and_single_time_applies_both(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 10:00:00', '2026-08-01 12:00:00');

        $this->bulkEdit($event, ['selectedDay' => '2026-08-05', 'selectedEndTime' => '14:00']);

        $this->assertTimes($event, '2026-08-05 10:00:00', '2026-08-05 14:00:00');
    }

    #[Test]
    public function bulk_edit_day_only_keeps_duration(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 20:00:00', '2026-08-02 02:00:00');

        $this->bulkEdit($event, ['selectedDay' => '2026-08-10']);

        $this->assertTimes($event, '2026-08-10 20:00:00', '2026-08-11 02:00:00');
    }

    #[Test]
    public function bulk_edit_day_and_both_times_like_single_day_creation(): void
    {
        $this->actingAsAdmin();
        $event = $this->event('2026-08-01 10:00:00', '2026-08-01 12:00:00');

        $this->bulkEdit($event, [
            'selectedDay' => '2026-08-05',
            'selectedStartTime' => '22:00',
            'selectedEndTime' => '01:00',
        ]);

        $this->assertTimes($event, '2026-08-05 22:00:00', '2026-08-06 01:00:00');
    }

    private function event(string $start, string $end): Event
    {
        return Event::factory()->create([
            'room_id' => Room::factory()->create()->id,
            'start_time' => $start,
            'end_time' => $end,
            'allDay' => false,
        ]);
    }

    /**
     * @param array<int, int> $eventIds
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function multiEditPayload(array $eventIds, array $overrides): array
    {
        return array_merge([
            'events' => $eventIds,
            'newRoomId' => null,
            'calculationType' => 1,
            'value' => 0,
            'type' => 2,
            'date' => null,
        ], $overrides);
    }

    /**
     * @param array<string, string> $times
     */
    private function bulkEdit(Event $event, array $times): void
    {
        $this->postJson(route('events.bulk-multi-edit'), array_merge(['eventIds' => [$event->id]], $times))
            ->assertSuccessful();
    }

    private function assertTimes(Event $event, string $expectedStart, string $expectedEnd): void
    {
        $event->refresh();
        $this->assertSame($expectedStart, $event->start_time->format('Y-m-d H:i:s'));
        $this->assertSame($expectedEnd, $event->end_time->format('Y-m-d H:i:s'));
    }
}
