<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/** Status einschalten: Termine ohne Status bekamen fest Status 1, ein 500er wenn es ihn nicht mehr gibt. */
final class EventStatusEnableSettingTest extends FeatureTestCase
{
    #[Test]
    public function enabling_status_assigns_the_default_status_to_events_without_status(): void
    {
        $this->actingAsAdmin();
        EventStatus::query()->update(['default' => false]);
        $defaultStatus = EventStatus::factory()->create(['default' => true, 'order' => 50]);
        $event = Event::factory()->create(['event_status_id' => null]);

        $this->patch(route('event_status.update_settings'), ['enable_status' => true])->assertSessionHasNoErrors();

        self::assertSame($defaultStatus->id, (int) $event->fresh()->event_status_id);
    }

    #[Test]
    public function enabling_status_does_not_fail_when_status_one_is_gone(): void
    {
        $this->actingAsAdmin();
        Event::query()->whereNotNull('event_status_id')->update(['event_status_id' => null]);
        EventStatus::query()->whereKey(1)->forceDelete();
        $remaining = EventStatus::factory()->create(['default' => false, 'order' => 1]);
        EventStatus::query()->whereKeyNot($remaining->id)->update(['default' => false]);
        $event = Event::factory()->create(['event_status_id' => null]);

        $this->patch(route('event_status.update_settings'), ['enable_status' => true])->assertSessionHasNoErrors();

        self::assertNotNull($event->fresh()->event_status_id);
        self::assertNotSame(1, (int) $event->fresh()->event_status_id);
    }
}
