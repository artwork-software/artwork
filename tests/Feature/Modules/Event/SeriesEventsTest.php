<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Support\Facades\Event as EventFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Wiederholungstermine: Anlegen (Turnus/Wochentage/Anzahl), Bearbeiten und Löschen mit Reichweite,
 * Turnus-/Ende-Abgleich ohne Duplikate, Lösen, Wiederherstellen (KONZEPT_Wiederholungstermine.md).
 */
final class SeriesEventsTest extends FeatureTestCase
{
    private EventType $eventType;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        EventFacade::fake();
        $this->actingAsAdmin();
        $this->eventType = EventType::factory()->create();
        $this->room = Room::factory()->create();
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'start' => '2026-10-05 10:00', // Montag
            'end' => '2026-10-05 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $this->eventType->id,
            'roomId' => $this->room->id,
            'title' => 'Serie',
            'eventName' => 'Probe',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{Event, SeriesEvents}
     */
    private function createWeeklySeries(array $overrides = []): array
    {
        $this->postJson(route('events.store'), $this->payload(array_merge([
            'is_series' => true,
            'seriesFrequency' => 2,
            'seriesEndDate' => '2026-11-02', // Mo 05.10. .. Mo 02.11. = 5 Termine
        ], $overrides)))->assertSuccessful();

        /** @var Event $first */
        $first = Event::query()->orderBy('start_time')->firstOrFail();

        return [$first, SeriesEvents::query()->findOrFail($first->series_id)];
    }

    /**
     * @return array<int, string>
     */
    private function seriesDates(int $seriesId, bool $withTrashed = false): array
    {
        $query = $withTrashed ? Event::withTrashed() : Event::query();

        return $query->where('series_id', $seriesId)
            ->orderBy('start_time')
            ->get()
            ->map(fn (Event $e) => $e->start_time->format('Y-m-d H:i'))
            ->all();
    }

    // ------------------------------------------------------------------ Anlegen

    #[Test]
    public function weekly_series_with_end_date_creates_all_occurrences(): void
    {
        [$first, $series] = $this->createWeeklySeries();

        $this->assertSame([
            '2026-10-05 10:00', '2026-10-12 10:00', '2026-10-19 10:00', '2026-10-26 10:00', '2026-11-02 10:00',
        ], $this->seriesDates($series->id));
        $this->assertTrue($first->fresh()->is_series);
        $this->assertSame('2026-10-05', $series->start_date->toDateString());
        $this->assertSame('2026-11-02', $series->end_date->toDateString());
        $this->assertNull($series->weekdays);
        $this->assertSame(2, Event::query()->where('series_id', $series->id)->first()->end_time->hour - 10);
    }

    #[Test]
    public function weekly_series_with_weekdays_creates_occurrences_on_each_weekday(): void
    {
        [, $series] = $this->createWeeklySeries([
            'seriesEndDate' => '2026-10-18',
            'seriesWeekdays' => [3, 5], // Mi + Fr; Montag (Anker) wird automatisch ergänzt
        ]);

        $this->assertSame([1, 3, 5], $series->weekdays);
        $this->assertSame([
            '2026-10-05 10:00', '2026-10-07 10:00', '2026-10-09 10:00',
            '2026-10-12 10:00', '2026-10-14 10:00', '2026-10-16 10:00',
        ], $this->seriesDates($series->id));
    }

    #[Test]
    public function biweekly_series_with_weekdays_skips_every_other_week(): void
    {
        [, $series] = $this->createWeeklySeries([
            'seriesFrequency' => 3,
            'seriesEndDate' => '2026-10-25',
            'seriesWeekdays' => [1, 4],
        ]);

        $this->assertSame([
            '2026-10-05 10:00', '2026-10-08 10:00', '2026-10-19 10:00', '2026-10-22 10:00',
        ], $this->seriesDates($series->id));
    }

    #[Test]
    public function series_with_occurrence_count_creates_exactly_that_many_events(): void
    {
        [, $series] = $this->createWeeklySeries([
            'seriesFrequency' => 1,
            'seriesEndDate' => null,
            'seriesOccurrenceCount' => 4,
        ]);

        $this->assertNull($series->end_date);
        $this->assertSame(4, $series->occurrence_count);
        $this->assertSame([
            '2026-10-05 10:00', '2026-10-06 10:00', '2026-10-07 10:00', '2026-10-08 10:00',
        ], $this->seriesDates($series->id));
    }

    #[Test]
    public function monthly_series_keeps_day_of_month_without_overflow(): void
    {
        $this->postJson(route('events.store'), $this->payload([
            'start' => '2026-01-31 10:00',
            'end' => '2026-01-31 12:00',
            'is_series' => true,
            'seriesFrequency' => 4,
            'seriesEndDate' => '2026-04-30',
        ]))->assertSuccessful();

        $seriesId = Event::query()->firstOrFail()->series_id;
        $this->assertSame([
            '2026-01-31 10:00', '2026-02-28 10:00', '2026-03-31 10:00', '2026-04-30 10:00',
        ], $this->seriesDates($seriesId));
    }

    #[Test]
    public function preview_returns_occurrences_with_room_collisions(): void
    {
        Event::factory()->create([
            'room_id' => $this->room->id,
            'start_time' => '2026-10-12 11:00',
            'end_time' => '2026-10-12 13:00',
        ]);

        $response = $this->postJson(route('events.series.preview'), [
            'start' => '2026-10-05 10:00',
            'end' => '2026-10-05 12:00',
            'roomId' => $this->room->id,
            'seriesFrequency' => 2,
            'seriesEndDate' => '2026-10-19',
        ])->assertSuccessful();

        $response->assertJsonPath('total', 3);
        $response->assertJsonPath('occurrences.0.start', '2026-10-05 10:00');
        $response->assertJsonPath('occurrences.0.collisions', 0);
        $response->assertJsonPath('occurrences.1.collisions', 1);
        $response->assertJsonPath('occurrences.2.collisions', 0);
    }

    #[Test]
    public function preview_requires_an_end_date_or_occurrence_count(): void
    {
        $this->postJson(route('events.series.preview'), [
            'start' => '2026-10-05 10:00',
            'end' => '2026-10-05 12:00',
            'seriesFrequency' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors(['seriesEndDate']);
    }

    // ------------------------------------------------------------------ Lesen

    #[Test]
    public function show_returns_definition_and_all_occurrences_including_trashed(): void
    {
        [$first, $series] = $this->createWeeklySeries();
        $second = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(1)->first();
        $second->delete();

        $response = $this->getJson(route('events.series.show', $first))->assertSuccessful();

        $response->assertJsonPath('series.frequency_id', 2);
        $response->assertJsonPath('series.end_date', '2026-11-02');
        $response->assertJsonPath('counts.total', 5);
        $response->assertJsonPath('counts.active', 4);
        $response->assertJsonPath('counts.trashed', 1);
        $response->assertJsonPath('occurrences.0.isCurrent', true);
        $response->assertJsonPath('occurrences.1.isTrashed', true);
        $response->assertJsonPath('occurrences.1.roomName', $this->room->name);
    }

    // ------------------------------------------------------------------ Bearbeiten: Reichweite

    #[Test]
    public function editing_a_single_occurrence_with_stale_default_frequency_does_not_rebuild_the_series(): void
    {
        // Regression: früher schrieb ein Default-Turnus „wöchentlich“ eine tägliche Serie still um
        // und löschte alle künftigen Termine hart.
        [$first, $series] = $this->createWeeklySeries([
            'seriesFrequency' => 1,
            'seriesEndDate' => '2026-10-09',
        ]);
        $ids = Event::query()->where('series_id', $series->id)->pluck('id')->all();
        $this->assertCount(5, $ids);

        $this->putJson(route('events.update', $first), $this->payload([
            'description' => 'nur Text geändert',
            'is_series' => true,
            'seriesFrequency' => 2,
            'seriesEndDate' => null,
            'seriesScope' => 'single',
        ]))->assertSuccessful();

        $this->assertSame(1, (int) $series->fresh()->frequency_id);
        $this->assertSame($ids, Event::query()->where('series_id', $series->id)->orderBy('start_time')->pluck('id')->all());
        $this->assertSame('nur Text geändert', $first->fresh()->description);
        $this->assertNull(Event::query()->whereKey($ids[1])->first()->description);
    }

    #[Test]
    public function editing_with_scope_all_propagates_changed_fields_and_time_delta(): void
    {
        [$first, $series] = $this->createWeeklySeries();

        $this->putJson(route('events.update', $first), $this->payload([
            'start' => '2026-10-05 11:00',
            'end' => '2026-10-05 13:30',
            'eventName' => 'Neuer Name',
            'seriesScope' => 'all',
        ]))->assertSuccessful();

        $events = Event::query()->where('series_id', $series->id)->orderBy('start_time')->get();
        $this->assertSame(['Neuer Name'], $events->pluck('eventName')->unique()->values()->all());
        $this->assertSame('2026-10-12 11:00', $events[1]->start_time->format('Y-m-d H:i'));
        $this->assertSame('2026-10-12 13:30', $events[1]->end_time->format('Y-m-d H:i'));
        $this->assertSame('2026-11-02 11:00', $events[4]->start_time->format('Y-m-d H:i'));
    }

    #[Test]
    public function editing_with_scope_following_leaves_earlier_occurrences_untouched(): void
    {
        [, $series] = $this->createWeeklySeries();
        $third = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(2)->firstOrFail();
        $otherRoom = Room::factory()->create();

        $this->putJson(route('events.update', $third), $this->payload([
            'start' => '2026-10-19 10:00',
            'end' => '2026-10-19 12:00',
            'roomId' => $otherRoom->id,
            'seriesScope' => 'following',
        ]))->assertSuccessful();

        $rooms = Event::query()->where('series_id', $series->id)->orderBy('start_time')->pluck('room_id')->all();
        $this->assertSame([
            $this->room->id, $this->room->id, $otherRoom->id, $otherRoom->id, $otherRoom->id,
        ], $rooms);
    }

    #[Test]
    public function moving_a_single_occurrence_marks_it_as_exception_and_protects_it_from_series_deltas(): void
    {
        [$first, $series] = $this->createWeeklySeries();
        $second = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(1)->firstOrFail();

        $this->putJson(route('events.update', $second), $this->payload([
            'start' => '2026-10-13 15:00',
            'end' => '2026-10-13 16:00',
            'seriesScope' => 'single',
        ]))->assertSuccessful();
        $this->assertTrue($second->fresh()->is_series_exception);

        // Zeit-Delta auf die ganze Serie: die Ausnahme behält ihre Zeit, bekommt aber Feldänderungen
        $this->putJson(route('events.update', $first), $this->payload([
            'start' => '2026-10-05 09:00',
            'end' => '2026-10-05 11:00',
            'eventName' => 'Alle',
            'seriesScope' => 'all',
        ]))->assertSuccessful();

        $second->refresh();
        $this->assertSame('2026-10-13 15:00', $second->start_time->format('Y-m-d H:i'));
        $this->assertSame('Alle', $second->eventName);
        $third = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(2)->firstOrFail();
        $this->assertSame('2026-10-19 09:00', $third->start_time->format('Y-m-d H:i'));
    }

    // ------------------------------------------------------------------ Turnus / Ende

    #[Test]
    public function extending_the_end_date_only_appends_occurrences(): void
    {
        [$first, $series] = $this->createWeeklySeries();
        $ids = Event::query()->where('series_id', $series->id)->pluck('id')->all();

        $this->putJson(route('events.update', $first), $this->payload([
            'is_series' => true,
            'seriesFrequency' => 2,
            'seriesEndDate' => '2026-11-16',
            'seriesScope' => 'all',
        ]))->assertSuccessful();

        $this->assertSame([
            '2026-10-05 10:00', '2026-10-12 10:00', '2026-10-19 10:00', '2026-10-26 10:00',
            '2026-11-02 10:00', '2026-11-09 10:00', '2026-11-16 10:00',
        ], $this->seriesDates($series->id));
        $this->assertEmpty(array_diff($ids, Event::query()->where('series_id', $series->id)->pluck('id')->all()));
        $this->assertSame('2026-11-16', $series->fresh()->end_date->toDateString());
    }

    #[Test]
    public function shortening_the_end_date_trashes_later_occurrences_instead_of_deleting_them(): void
    {
        [$first, $series] = $this->createWeeklySeries();

        $this->putJson(route('events.update', $first), $this->payload([
            'is_series' => true,
            'seriesFrequency' => 2,
            'seriesEndDate' => '2026-10-19',
            'seriesScope' => 'all',
        ]))->assertSuccessful();

        $this->assertSame(
            ['2026-10-05 10:00', '2026-10-12 10:00', '2026-10-19 10:00'],
            $this->seriesDates($series->id)
        );
        $this->assertSame(2, Event::onlyTrashed()->where('series_id', $series->id)->count());
    }

    #[Test]
    public function changing_the_frequency_rebuilds_future_occurrences_without_duplicates_and_keeps_exceptions(): void
    {
        [$first, $series] = $this->createWeeklySeries(['seriesEndDate' => '2026-10-26']); // 4 Termine
        $events = Event::query()->where('series_id', $series->id)->orderBy('start_time')->get();

        // Termin 3 einzeln verschoben -> Ausnahme; Termin 4 vom Nutzer in den Papierkorb gelegt
        $events[2]->forceFill(['is_series_exception' => true, 'start_time' => '2026-10-20 10:00', 'end_time' => '2026-10-20 12:00'])->save();
        $events[3]->delete();

        $impact = $this->postJson(route('events.series.impact', $first), [
            'seriesFrequency' => 1,
            'seriesEndDate' => '2026-10-26',
        ])->assertSuccessful();
        $impact->assertJsonPath('rebuild', true);
        $impact->assertJsonPath('trash', 1);
        $impact->assertJsonPath('exceptions', 1);

        $this->putJson(route('events.update', $first), $this->payload([
            'is_series' => true,
            'seriesFrequency' => 1,
            'seriesEndDate' => '2026-10-26',
            'seriesScope' => 'all',
        ]))->assertSuccessful();

        $dates = $this->seriesDates($series->id);
        // Täglich 05.10.-26.10., aber: 20.10. bleibt die Ausnahme, 26.10. liegt im Papierkorb (kein Duplikat)
        $this->assertSame(21, count($dates));
        $this->assertContains('2026-10-20 10:00', $dates);
        $this->assertNotContains('2026-10-26 10:00', $dates);
        $this->assertSame(count($dates), count(array_unique(array_map(fn ($d) => substr($d, 0, 10), $dates))));
        $this->assertTrue($events[2]->fresh()->is_series_exception);
        $this->assertSame(1, (int) $series->fresh()->frequency_id);
        // der frühere Termin 2 (12.10.) wurde vom Neu-Ausrollen in den Papierkorb gelegt und neu erzeugt
        $this->assertNotNull(Event::withTrashed()->find($events[1]->id)->deleted_at);
        $this->assertContains('2026-10-12 10:00', $dates);
    }

    #[Test]
    public function rebuild_trashes_shifts_of_replaced_occurrences_and_reports_them(): void
    {
        [$first, $series] = $this->createWeeklySeries(['seriesEndDate' => '2026-10-19']);
        $second = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(1)->firstOrFail();
        $shift = Shift::factory()->create(['event_id' => $second->id, 'craft_id' => Craft::factory()->create()->id]);

        $this->postJson(route('events.series.impact', $first), [
            'seriesFrequency' => 3,
            'seriesEndDate' => '2026-10-19',
        ])->assertSuccessful()->assertJsonPath('shifts', 1)->assertJsonPath('trash', 2);

        $this->putJson(route('events.update', $first), $this->payload([
            'is_series' => true,
            'seriesFrequency' => 3,
            'seriesEndDate' => '2026-10-19',
            'seriesScope' => 'all',
        ]))->assertSuccessful();

        $this->assertSame(['2026-10-05 10:00', '2026-10-19 10:00'], $this->seriesDates($series->id));
        $this->assertNotNull(Shift::withTrashed()->find($shift->id)->deleted_at);
    }

    #[Test]
    public function turning_a_single_event_into_a_series_while_editing_rolls_out_following_occurrences(): void
    {
        $event = Event::factory()->create([
            'room_id' => $this->room->id,
            'event_type_id' => $this->eventType->id,
            'start_time' => '2026-10-05 10:00',
            'end_time' => '2026-10-05 12:00',
            'is_series' => false,
        ]);

        $this->putJson(route('events.update', $event), $this->payload([
            'is_series' => true,
            'seriesFrequency' => 2,
            'seriesEndDate' => '2026-10-19',
        ]))->assertSuccessful();

        $event->refresh();
        $this->assertTrue($event->is_series);
        $this->assertSame(
            ['2026-10-05 10:00', '2026-10-12 10:00', '2026-10-19 10:00'],
            $this->seriesDates($event->series_id)
        );
    }

    // ------------------------------------------------------------------ Löschen / Lösen / Wiederherstellen

    #[Test]
    public function deleting_with_scope_following_trashes_this_and_later_occurrences_only(): void
    {
        [, $series] = $this->createWeeklySeries();
        $third = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(2)->firstOrFail();

        $this->deleteJson(route('events.series.delete', $third), ['scope' => 'following'])
            ->assertSuccessful()
            ->assertJsonPath('trashed', 3);

        $this->assertSame(['2026-10-05 10:00', '2026-10-12 10:00'], $this->seriesDates($series->id));
        $this->assertSame(3, Event::onlyTrashed()->where('series_id', $series->id)->count());
    }

    #[Test]
    public function deleting_with_scope_all_trashes_the_whole_series(): void
    {
        [$first, $series] = $this->createWeeklySeries();

        $this->deleteJson(route('events.series.delete', $first), ['scope' => 'all'])
            ->assertSuccessful()
            ->assertJsonPath('trashed', 5);

        $this->assertSame([], $this->seriesDates($series->id));
        $this->assertSame(5, Event::onlyTrashed()->where('series_id', $series->id)->count());
    }

    #[Test]
    public function detaching_a_single_occurrence_keeps_the_rest_of_the_series(): void
    {
        [, $series] = $this->createWeeklySeries();
        $second = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(1)->firstOrFail();

        $this->postJson(route('events.series.detach', $second), ['mode' => 'single'])
            ->assertSuccessful()
            ->assertJsonPath('trashed', 0);

        $second->refresh();
        $this->assertFalse($second->is_series);
        $this->assertNull($second->series_id);
        $this->assertCount(4, $this->seriesDates($series->id));
    }

    #[Test]
    public function ending_a_series_trashes_the_other_occurrences_and_detaches_this_one(): void
    {
        [, $series] = $this->createWeeklySeries();
        $second = Event::query()->where('series_id', $series->id)->orderBy('start_time')->skip(1)->firstOrFail();

        $this->postJson(route('events.series.detach', $second), ['mode' => 'end'])
            ->assertSuccessful()
            ->assertJsonPath('trashed', 4);

        $this->assertFalse($second->fresh()->is_series);
        $this->assertSame([], $this->seriesDates($series->id));
        $this->assertSame(4, Event::onlyTrashed()->where('series_id', $series->id)->count());
        $this->assertNotNull(SeriesEvents::query()->find($series->id), 'Serie bleibt für den Papierkorb erhalten');
    }

    #[Test]
    public function a_series_can_be_restored_from_the_trash_as_a_whole(): void
    {
        [$first, $series] = $this->createWeeklySeries();
        $this->deleteJson(route('events.series.delete', $first), ['scope' => 'all'])->assertSuccessful();

        $this->patchJson(route('events.series.restore', $series))
            ->assertSuccessful()
            ->assertJsonPath('restored', 5);

        $this->assertCount(5, $this->seriesDates($series->id));
        $this->assertSame(0, Event::onlyTrashed()->where('series_id', $series->id)->count());
    }

    #[Test]
    public function unchecking_series_while_editing_does_not_touch_the_series(): void
    {
        [$first, $series] = $this->createWeeklySeries();

        $this->putJson(route('events.update', $first), $this->payload([
            'is_series' => false,
            'seriesScope' => 'single',
        ]))->assertSuccessful();

        $this->assertCount(5, $this->seriesDates($series->id));
        $this->assertTrue($first->fresh()->is_series);
    }
}
