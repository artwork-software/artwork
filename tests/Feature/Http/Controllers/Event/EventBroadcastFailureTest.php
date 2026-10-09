<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Nicht erreichbarer WebSocket-Server: Termin-Änderungen sind gespeichert, die Antwort darf keine 500 sein
 * (erneutes Speichern legte sonst z. B. Serien doppelt an), und nach dem ersten Fehler wird im selben Request
 * nicht weiter versucht (jeder Versuch kostet beim hängenden Server den vollen Timeout).
 */
final class EventBroadcastFailureTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // FeatureTestCase fakt den Bus – Broadcasts (ShouldBroadcastNow) liefen dann nie wirklich
        Bus::swap($this->app->make(Dispatcher::class));

        FailingBroadcaster::$attempts = 0;
        Broadcast::extend('failing', static fn (): Broadcaster => new FailingBroadcaster());
        config([
            'broadcasting.default' => 'failing',
            'broadcasting.connections.failing' => ['driver' => 'failing'],
        ]);
    }

    #[Test]
    public function storing_a_series_succeeds_once_although_broadcasting_fails(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $eventType = EventType::factory()->create();

        $this->postJson(route('events.store'), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Serie',
            'eventName' => 'Probe',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'isPlanning' => false,
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_DAILY,
            'seriesOccurrenceCount' => 3,
        ])->assertSuccessful();

        $this->assertSame(3, Event::query()->where('room_id', $room->id)->count());
        $this->assertSame(1, FailingBroadcaster::$attempts);
    }

    #[Test]
    public function extending_a_series_succeeds_although_broadcasting_fails(): void
    {
        $admin = $this->actingAsAdmin();
        $room = Room::factory()->create();
        $series = SeriesEvents::query()->create([
            'frequency_id' => SeriesEvents::FREQUENCY_WEEKLY,
            'end_date' => '2026-11-24',
        ]);
        $events = collect(range(0, 2))->map(fn (int $week): Event => Event::factory()->create([
            'user_id' => $admin->id,
            'room_id' => $room->id,
            'project_id' => null,
            'is_series' => true,
            'series_id' => $series->id,
            'start_time' => Carbon::parse('2026-11-10 10:00:00')->addWeeks($week)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::parse('2026-11-10 12:00:00')->addWeeks($week)->format('Y-m-d H:i:s'),
            'allDay' => false,
        ]));
        $first = $events->first();

        $this->putJson(route('events.update', $first), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $first->event_type_id,
            'roomId' => $room->id,
            'title' => 'Serie',
            'eventName' => 'Umbenannt',
            'isOption' => false,
            'noNotifications' => true,
            'seriesScope' => 'all',
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_WEEKLY,
            'seriesEndDate' => '2026-12-08',
        ])->assertSuccessful();

        $seriesEvents = Event::query()->where('series_id', $series->id)->get();
        $this->assertCount(5, $seriesEvents);
        $this->assertSame(['Umbenannt'], $seriesEvents->pluck('eventName')->unique()->values()->all());
        $this->assertSame(1, FailingBroadcaster::$attempts);
    }
}

/**
 * Broadcaster eines nicht erreichbaren WebSocket-Servers; zählt die Sende-Versuche.
 */
final class FailingBroadcaster implements Broadcaster
{
    public static int $attempts = 0;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- Interface ohne Typen
    public function auth($request): mixed
    {
        return null;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- Interface ohne Typen
    public function validAuthenticationResponse($request, $result): mixed
    {
        return null;
    }

    /**
     * @param array<int, mixed> $channels
     * @param array<string, mixed> $payload
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- Interface ohne Typen
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        self::$attempts++;

        // So meldet der PusherBroadcaster (Reverb) einen nicht erreichbaren Server
        throw new BroadcastException('Pusher error: cURL error 7: Failed to connect to reverb port 8080.');
    }
}
