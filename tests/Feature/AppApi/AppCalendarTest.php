<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppCalendarTest extends TestCase
{
    #[Test]
    public function calendarRequiresAuthentication(): void
    {
        $this->getJson(route('app.v1.calendar'))->assertUnauthorized();
    }

    #[Test]
    public function calendarDefaultsToCurrentWeek(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.calendar'))
            ->assertOk()
            ->assertJsonPath('start', now()->startOfWeek()->toDateString())
            ->assertJsonPath('end', now()->endOfWeek()->toDateString())
            ->assertJsonCount(7, 'days');
    }

    #[Test]
    public function calendarRejectsRangesOverTheCap(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $this->getJson(route('app.v1.calendar', [
            'start' => now()->toDateString(),
            'end' => now()->addDays(90)->toDateString(),
        ]))->assertUnprocessable();
    }

    #[Test]
    public function calendarReturnsEventsWithTypeProjectAndRoom(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $room = Room::factory()->create(['relevant_for_disposition' => true]);
        $date = now()->startOfWeek()->addDay();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => $date->copy()->setTime(19, 30),
            'end_time' => $date->copy()->setTime(22, 0),
            'is_planning' => false,
            'allDay' => false,
        ]);

        $response = $this->getJson(route('app.v1.calendar'))->assertOk();
        $response->assertJsonStructure([
            'start',
            'end',
            'days' => [['date', 'holidays', 'events']],
        ]);

        $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());
        $this->assertNotNull($day);
        $this->assertCount(1, $day['events']);

        $eventPayload = $day['events'][0];
        $this->assertSame($event->id, $eventPayload['id']);
        $this->assertFalse($eventPayload['all_day']);
        $this->assertSame($room->id, $eventPayload['room']['id']);
        $this->assertSame($event->event_type_id, $eventPayload['event_type']['id']);
        $this->assertSame($event->project_id, $eventPayload['project']['id']);
    }

    #[Test]
    public function calendarEmitsMultiDayEventsOnEverySpannedDay(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $room = Room::factory()->create(['relevant_for_disposition' => true]);
        $start = now()->startOfWeek();
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => $start->copy()->setTime(10, 0),
            'end_time' => $start->copy()->addDays(2)->setTime(18, 0),
            'is_planning' => false,
        ]);

        $response = $this->getJson(route('app.v1.calendar'))->assertOk();
        $days = collect($response->json('days'));

        foreach (range(0, 2) as $offset) {
            $day = $days->firstWhere('date', $start->copy()->addDays($offset)->toDateString());
            $this->assertCount(1, $day['events'], "expected event on day offset {$offset}");
        }
        $this->assertCount(0, $days->firstWhere('date', $start->copy()->addDays(3)->toDateString())['events']);
    }

    #[Test]
    public function calendarExcludesPlanningEventsAndNonDispositionRooms(): void
    {
        Passport::actingAs(User::factory()->create(), ['app']);

        $date = now()->startOfWeek()->addDay();
        Event::factory()->create([
            'room_id' => Room::factory()->create(['relevant_for_disposition' => true])->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(12, 0),
            'is_planning' => true,
        ]);
        Event::factory()->create([
            'room_id' => Room::factory()->create(['relevant_for_disposition' => false])->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(12, 0),
            'is_planning' => false,
        ]);

        $response = $this->getJson(route('app.v1.calendar'))->assertOk();
        $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());
        $this->assertCount(0, $day['events']);
    }
}
