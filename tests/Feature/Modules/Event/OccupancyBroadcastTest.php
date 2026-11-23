<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Events\RemoveEvent;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCommentService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\SubEventService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event as EventFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Live-Updates offener Kalender: Einzeltermine über die Raumkanäle (EventCreated/RemoveEvent), Stapel über das
 * Sammel-Signal OccupancyUpdated mit dem Zeitraum der betroffenen Termine (Kalender laden nur diese Monate nach).
 */
final class OccupancyBroadcastTest extends FeatureTestCase
{
    private const BROADCASTS = [OccupancyUpdated::class, RemoveEvent::class, EventCreated::class];

    /**
     * @param Collection<int, Event> $events
     */
    private function deleteAll(Collection $events): void
    {
        app(EventService::class)->deleteAll(
            $events,
            app(ShiftsQualificationsService::class),
            app(ShiftUserService::class),
            app(ShiftFreelancerService::class),
            app(ShiftServiceProviderService::class),
            app(ChangeService::class),
            app(EventCommentService::class),
            app(TimelineService::class),
            app(ShiftService::class),
            app(SubEventService::class),
            app(NotificationService::class),
            app(ProjectTabService::class),
            false,
        );
    }

    #[Test]
    public function the_signal_carries_the_date_range_of_all_events(): void
    {
        $signal = OccupancyUpdated::forEvents([
            new Event(['start_time' => '2026-11-20 10:00:00', 'end_time' => '2026-11-20 12:00:00']),
            new Event(['start_time' => '2026-11-03 22:00:00', 'end_time' => '2026-11-04 02:00:00']),
            new Event(['start_time' => '2026-12-01 09:00:00', 'end_time' => '2026-12-02 18:00:00']),
        ]);

        $this->assertSame('occupancy.updated', $signal->broadcastAs());
        $this->assertSame(['start' => '2026-11-03', 'end' => '2026-12-02'], $signal->broadcastWith());
        $this->assertSame(['start' => null, 'end' => null], (new OccupancyUpdated())->broadcastWith());
    }

    #[Test]
    public function deleting_many_events_sends_one_signal_for_their_range_instead_of_one_per_event(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $events = new Collection([
            Event::factory()->create(['room_id' => $room->id, 'start_time' => '2026-11-10 10:00:00', 'end_time' => '2026-11-10 12:00:00']),
            Event::factory()->create(['room_id' => $room->id, 'start_time' => '2027-01-15 10:00:00', 'end_time' => '2027-01-15 12:00:00']),
        ]);
        EventFacade::fake(self::BROADCASTS);

        $this->deleteAll($events);

        EventFacade::assertDispatchedTimes(OccupancyUpdated::class, 1);
        EventFacade::assertDispatched(
            OccupancyUpdated::class,
            fn (OccupancyUpdated $signal): bool => $signal->start === '2026-11-10' && $signal->end === '2027-01-15'
        );
        EventFacade::assertNotDispatched(RemoveEvent::class);
    }

    #[Test]
    public function deleting_a_single_event_updates_its_room_channel_only(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['room_id' => Room::factory()->create()->id]);
        EventFacade::fake(self::BROADCASTS);

        $this->deleteJson(route('events.delete', $event))->assertSuccessful();

        EventFacade::assertDispatched(RemoveEvent::class, fn (RemoveEvent $removed): bool => $removed->event->is($event));
        EventFacade::assertNotDispatched(OccupancyUpdated::class);
    }

    #[Test]
    public function restoring_a_single_event_shows_it_again_in_open_calendars(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['room_id' => Room::factory()->create()->id]);
        $event->delete();
        EventFacade::fake(self::BROADCASTS);

        app(EventService::class)->restore(
            $event->fresh() ?? Event::withTrashed()->findOrFail($event->id),
            app(ShiftsQualificationsService::class),
            app(ChangeService::class),
            app(EventCommentService::class),
            app(TimelineService::class),
            app(ShiftService::class),
            app(SubEventService::class),
        );

        EventFacade::assertDispatched(
            EventCreated::class,
            fn (EventCreated $created): bool => $created->event->is($event) && $created->roomId === $event->room_id
        );
        EventFacade::assertNotDispatched(OccupancyUpdated::class);
    }

    #[Test]
    public function a_new_event_reaches_open_calendars_also_in_project_period_mode(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        EventFacade::fake(self::BROADCASTS);

        $this->post(route('events.store'), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => EventType::factory()->create()->id,
            'roomId' => $room->id,
            'title' => 'Probe',
            'eventName' => 'Probe',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'isPlanning' => false,
            'is_series' => false,
            'showProjectPeriodInCalendar' => true,
        ])->assertRedirect();

        EventFacade::assertDispatched(EventCreated::class, fn (EventCreated $created): bool => $created->roomId === $room->id);
        EventFacade::assertNotDispatched(OccupancyUpdated::class);
    }
}
