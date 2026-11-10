<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventBulkConvertToPlanningTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_convert_events_in_bulk(): void
    {
        $event = Event::factory()->create(['is_planning' => false]);

        $this->postJson(route('events.bulk.convert-to-planning'), ['eventIds' => [$event->id]])
            ->assertUnauthorized();
    }

    #[Test]
    public function users_without_planning_edit_right_cannot_convert_events_in_bulk(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        $event = Event::factory()->create(['is_planning' => false]);

        $this->postJson(route('events.bulk.convert-to-planning'), ['eventIds' => [$event->id]])
            ->assertForbidden();

        $this->assertFalse((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function planning_editors_convert_the_selected_fixed_events(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_EDIT_PLANNING_CALENDAR]);
        $first = Event::factory()->create(['is_planning' => false]);
        $second = Event::factory()->create(['is_planning' => false]);
        $alreadyPlanned = Event::factory()->create(['is_planning' => true]);
        $notSelected = Event::factory()->create(['is_planning' => false]);

        $this->postJson(route('events.bulk.convert-to-planning'), [
            'eventIds' => [$first->id, $second->id, $alreadyPlanned->id, 999999],
        ])
            ->assertOk()
            ->assertJsonPath('converted', [$first->id, $second->id]);

        $this->assertTrue((bool) $first->fresh()->is_planning);
        $this->assertTrue((bool) $second->fresh()->is_planning);
        $this->assertTrue((bool) $alreadyPlanned->fresh()->is_planning);
        $this->assertFalse((bool) $notSelected->fresh()->is_planning);
    }

    #[Test]
    public function the_calendar_is_redirected_back_after_converting(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false]);

        $this->from(route('events'))
            ->post(route('events.bulk.convert-to-planning'), ['eventIds' => [$event->id]])
            ->assertRedirect(route('events'));

        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function the_selection_is_validated(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('events.bulk.convert-to-planning'), ['eventIds' => []])
            ->assertUnprocessable();
        $this->postJson(route('events.bulk.convert-to-planning'), ['eventIds' => ['abc']])
            ->assertUnprocessable();
    }
}
