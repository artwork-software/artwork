<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventCommentService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Event\Services\SubEventService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Illuminate\Support\Facades\Event as EventFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Wiederherstellen vieler Termine (z. B. Projekt aus dem Papierkorb) sendet einen Ping für den ganzen
 * Stapel statt einen je Termin.
 */
final class EventRestoreAllBroadcastTest extends FeatureTestCase
{
    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Event>|array<int, Event> $events
     */
    private function restoreAll(\Illuminate\Database\Eloquent\Collection|array $events): void
    {
        app(EventService::class)->restoreAll(
            $events,
            app(ShiftsQualificationsService::class),
            app(ChangeService::class),
            app(EventCommentService::class),
            app(TimelineService::class),
            app(ShiftService::class),
            app(SubEventService::class),
        );
    }

    #[Test]
    public function restoring_several_events_broadcasts_a_single_occupancy_ping(): void
    {
        $this->actingAsAdmin();
        $events = Event::factory()->count(3)->create();
        $events->each->delete();
        EventFacade::fake([OccupancyUpdated::class]);

        $this->restoreAll(Event::onlyTrashed()->whereKey($events->modelKeys())->get());

        $this->assertSame(0, Event::onlyTrashed()->whereKey($events->modelKeys())->count());
        EventFacade::assertDispatchedTimes(OccupancyUpdated::class, 1);
    }

    #[Test]
    public function restoring_nothing_broadcasts_nothing(): void
    {
        EventFacade::fake([OccupancyUpdated::class]);

        $this->restoreAll([]);

        EventFacade::assertNotDispatched(OccupancyUpdated::class);
    }
}
