<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SeasonSchedulePdfExportControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_create_season_schedule_pdf(): void
    {
        $this->post(route('calendar.export.season-schedule-pdf'))
            ->assertRedirect(route('login'));
    }

    /**
     * Replace the wkhtmltopdf wrapper so no binary is required; captures the view data.
     *
     * @param array<string, mixed>|null $capturedViewData
     */
    private function mockSnappyPdf(?array &$capturedViewData): void
    {
        $pdf = Mockery::mock(PdfWrapper::class);
        $pdf->shouldReceive('loadView')
            ->andReturnUsing(function (string $view, array $data) use (&$capturedViewData, $pdf) {
                $capturedViewData = $data;

                return $pdf;
            });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('setOption')->andReturnSelf();
        $pdf->shouldReceive('save')->andReturnSelf();

        $this->app->instance(PdfWrapper::class, $pdf);
    }

    /**
     * @return array<int, string> Projektnamen der Zelle Tag $dayNumber im Monat $monthIndex der Seite $pageIndex
     */
    private function entryNames(array $viewData, int $pageIndex, int $monthIndex, int $dayNumber): array
    {
        $day = $viewData['pages'][$pageIndex][$monthIndex]['days'][$dayNumber];

        return array_map(static fn (array $entry): string => $entry['name'], $day['entries'] ?? []);
    }

    #[Test]
    public function event_type_filter_limits_projects_to_days_with_matching_events(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['name' => 'Zauberflöte']);
        $matchingType = EventType::factory()->create();
        $otherType = EventType::factory()->create();

        Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => $project->id,
            'event_type_id' => $matchingType->id,
            'start_time' => '2026-03-05 10:00:00',
            'end_time' => '2026-03-05 12:00:00',
        ]);
        Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => $project->id,
            'event_type_id' => $otherType->id,
            'start_time' => '2026-03-06 10:00:00',
            'end_time' => '2026-03-06 12:00:00',
        ]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-03-01',
                'endDate' => '2026-03-31',
                'filter' => ['event_type_ids' => [$matchingType->id]],
            ])
            ->assertRedirectContains('download');

        self::assertNotNull($capturedViewData);
        self::assertSame(['Zauberflöte'], $this->entryNames($capturedViewData, 0, 0, 5));
        // Am 06.03. hat das Projekt nur einen Termin der anderen Terminart -> Zelle bleibt leer
        self::assertSame([], $this->entryNames($capturedViewData, 0, 0, 6));
        // Die Legende weist den aktiven Terminarten-Filter aus
        self::assertSame([$matchingType->name], $capturedViewData['eventTypeFilterNames']);
    }

    #[Test]
    public function multiple_events_of_same_project_on_one_day_are_counted(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['name' => 'Faust']);
        $eventType = EventType::factory()->create();

        foreach (['10:00', '15:00', '19:00'] as $hour) {
            Event::factory()->create([
                'room_id' => $room->id,
                'project_id' => $project->id,
                'event_type_id' => $eventType->id,
                'start_time' => "2026-03-10 {$hour}:00",
                'end_time' => "2026-03-10 21:00:00",
            ]);
        }

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-03-01',
                'endDate' => '2026-03-31',
            ])
            ->assertRedirectContains('download');

        self::assertNotNull($capturedViewData);
        $entries = $capturedViewData['pages'][0][0]['days'][10]['entries'];
        self::assertCount(1, $entries);
        self::assertSame('Faust', $entries[0]['name']);
        self::assertSame(3, $entries[0]['count']);
    }

    #[Test]
    public function multi_day_events_appear_on_every_day_of_their_duration(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['name' => 'Festival']);

        Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => $project->id,
            'start_time' => '2026-03-20 10:00:00',
            'end_time' => '2026-03-22 18:00:00',
        ]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-03-01',
                'endDate' => '2026-03-31',
            ])
            ->assertRedirectContains('download');

        self::assertNotNull($capturedViewData);
        foreach ([20, 21, 22] as $dayNumber) {
            self::assertSame(['Festival'], $this->entryNames($capturedViewData, 0, 0, $dayNumber));
        }
        self::assertSame([], $this->entryNames($capturedViewData, 0, 0, 19));
        self::assertSame([], $this->entryNames($capturedViewData, 0, 0, 23));
    }

    #[Test]
    public function seven_months_are_chunked_into_two_pages(): void
    {
        $user = User::factory()->create();
        Room::factory()->create(['user_id' => $user->id]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-01-01',
                'endDate' => '2026-07-31',
            ])
            ->assertRedirectContains('download');

        self::assertNotNull($capturedViewData);
        self::assertCount(2, $capturedViewData['pages']);
        self::assertCount(6, $capturedViewData['pages'][0]);
        self::assertCount(1, $capturedViewData['pages'][1]);
        // Monatsnamen sind locale-abhängig (Januar/January) -> nur Präfix und Jahr prüfen
        self::assertStringStartsWith('Janu', $capturedViewData['pages'][0][0]['label']);
        self::assertStringEndsWith('2026', $capturedViewData['pages'][0][0]['label']);
        self::assertStringStartsWith('Jul', $capturedViewData['pages'][1][0]['label']);
        // Jahresübergreifender Default-Titel bleibt Jahr-basiert
        self::assertSame('Spielplan 2026', $capturedViewData['title']);
    }

    #[Test]
    public function rows_for_non_existing_days_stay_empty_and_days_without_events_stay_blank(): void
    {
        $user = User::factory()->create();
        Room::factory()->create(['user_id' => $user->id]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-02-01',
                'endDate' => '2026-02-28',
            ])
            ->assertRedirectContains('download');

        self::assertNotNull($capturedViewData);
        $days = $capturedViewData['pages'][0][0]['days'];
        self::assertCount(31, $days);
        // Februar 2026 hat 28 Tage -> 29-31 existieren nicht, bleiben aber als Zeilen erhalten
        self::assertNull($days[29]);
        self::assertNull($days[30]);
        self::assertNull($days[31]);
        self::assertSame([], $days[1]['entries']);
    }

    #[Test]
    public function events_without_project_are_hidden_by_default_and_shown_on_request(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['user_id' => $user->id]);

        Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => null,
            'eventName' => 'Girls Day',
            'start_time' => '2026-03-09 10:00:00',
            'end_time' => '2026-03-09 12:00:00',
        ]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-03-01',
                'endDate' => '2026-03-31',
            ])
            ->assertRedirectContains('download');

        self::assertSame([], $this->entryNames($capturedViewData, 0, 0, 9));

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-03-01',
                'endDate' => '2026-03-31',
                'showEventsWithoutProject' => true,
            ])
            ->assertRedirectContains('download');

        self::assertSame(['Girls Day'], $this->entryNames($capturedViewData, 0, 0, 9));
    }

    #[Test]
    public function day_exact_period_grays_out_days_outside_the_range(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['name' => 'Frühjahrsgala']);

        // Ein Termin innerhalb und einer außerhalb des tagesgenauen Zeitraums
        Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => $project->id,
            'start_time' => '2026-03-20 10:00:00',
            'end_time' => '2026-03-20 12:00:00',
        ]);
        Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => $project->id,
            'start_time' => '2026-03-05 10:00:00',
            'end_time' => '2026-03-05 12:00:00',
        ]);

        $this->mockSnappyPdf($capturedViewData);

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-03-10',
                'endDate' => '2026-04-05',
            ])
            ->assertRedirectContains('download');

        self::assertNotNull($capturedViewData);
        // Beide angeschnittenen Monate erscheinen als volle Spalten
        self::assertCount(2, $capturedViewData['pages'][0]);

        $march = $capturedViewData['pages'][0][0]['days'];
        $april = $capturedViewData['pages'][0][1]['days'];

        // Tage vor dem Startdatum sind ausgegraut und leer, auch wenn dort Termine liegen
        self::assertTrue($march[5]['outOfRange']);
        self::assertSame([], $march[5]['entries']);
        self::assertFalse($march[10]['outOfRange']);
        self::assertSame(['Frühjahrsgala'], $this->entryNames($capturedViewData, 0, 0, 20));

        // Tage nach dem Enddatum ebenso
        self::assertFalse($april[5]['outOfRange']);
        self::assertTrue($april[6]['outOfRange']);
    }

    #[Test]
    public function period_longer_than_24_months_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('calendar.export.season-schedule-pdf'), [
                'startDate' => '2026-01-01',
                'endDate' => '2028-01-31',
            ])
            ->assertSessionHasErrors('endDate');
    }
}
