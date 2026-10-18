<?php

namespace Tests\Feature\Projects\Characterization;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Zeitleisten-Endpunkte im ProjectController (add/update/delete):
 * Autorisierung über EventPolicy::editTimeline (Dienstplanung oder Schreibrecht am Termin)
 * und der heutige DB-Effekt.
 */
final class ProjectTimelineRowsTest extends FeatureTestCase
{
    #[Test]
    public function first_timeline_row_starts_at_eight_on_event_start_date(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create([
            'start_time' => '2026-03-10 18:00:00',
            'end_time' => '2026-03-10 22:00:00',
        ]);

        $this->post(route('add.timeline.row', $event))->assertOk();

        $timeline = $event->timelines()->sole();
        $this->assertSame('2026-03-10', $timeline->start_date->format('Y-m-d'));
        $this->assertStringStartsWith('08:00', (string) $timeline->getRawOriginal('start'));
        $this->assertStringStartsWith('09:00', (string) $timeline->getRawOriginal('end'));
        $this->assertNull($timeline->description);
    }

    #[Test]
    public function next_timeline_row_continues_after_the_last_row(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create([
            'start_time' => '2026-03-10 18:00:00',
            'end_time' => '2026-03-10 22:00:00',
        ]);
        Timeline::factory()->create([
            'event_id' => $event->id,
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-10',
            'start' => '10:00',
            'end' => '11:30',
        ]);

        $this->post(route('add.timeline.row', $event))->assertOk();

        $this->assertSame(2, $event->timelines()->count());
        $newest = $event->timelines()->orderByDesc('id')->first();
        $this->assertStringStartsWith('11:30', (string) $newest->getRawOriginal('start'));
        $this->assertStringStartsWith('12:30', (string) $newest->getRawOriginal('end'));
    }

    #[Test]
    public function shift_planner_without_project_rights_can_add_timeline_row(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $event = Event::factory()->create();

        $this->post(route('add.timeline.row', $event))->assertOk();

        $this->assertSame(1, $event->timelines()->count());
    }

    #[Test]
    public function user_without_rights_cannot_add_timeline_row(): void
    {
        $this->actingAs(User::factory()->create());
        $event = Event::factory()->create();

        $this->post(route('add.timeline.row', $event))->assertForbidden();

        $this->assertSame(0, $event->timelines()->count());
    }

    #[Test]
    public function write_team_member_of_event_project_can_add_timeline_row(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $event = Event::factory()->create();
        $event->project->users()->attach($user->id, ['can_write' => true]);

        $this->post(route('add.timeline.row', $event))->assertOk();

        $this->assertSame(1, $event->timelines()->count());
    }

    #[Test]
    public function admin_can_update_timeline_row(): void
    {
        $this->actingAsAdmin();
        $timeline = Timeline::factory()->create();

        $this->patch(route('update.timeline', $timeline), [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-01',
            'start' => '14:00',
            'end' => '15:15',
            'description' => 'Aufbau',
        ])->assertOk();

        $timeline->refresh();
        $this->assertSame('Aufbau', $timeline->description);
        $this->assertSame('2026-04-01', $timeline->start_date->format('Y-m-d'));
        $this->assertStringStartsWith('14:00', (string) $timeline->getRawOriginal('start'));
        $this->assertStringStartsWith('15:15', (string) $timeline->getRawOriginal('end'));
    }

    #[Test]
    public function update_timeline_requires_dates_and_times(): void
    {
        $this->actingAsAdmin();
        $timeline = Timeline::factory()->create();

        $this->patchJson(route('update.timeline', $timeline), ['description' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date', 'start', 'end']);
    }

    #[Test]
    public function user_without_rights_cannot_update_timeline_row(): void
    {
        $this->actingAs(User::factory()->create());
        $timeline = Timeline::factory()->create(['description' => 'Original']);

        $this->patch(route('update.timeline', $timeline), [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-01',
            'start' => '14:00',
            'end' => '15:00',
            'description' => 'Manipuliert',
        ])->assertForbidden();

        $this->assertSame('Original', $timeline->fresh()->description);
    }

    #[Test]
    public function admin_can_delete_timeline_row(): void
    {
        $this->actingAsAdmin();
        $timeline = Timeline::factory()->create();

        $this->delete(route('delete.timeline.row', $timeline))->assertOk();

        $this->assertDatabaseMissing('timelines', ['id' => $timeline->id]);
    }

    #[Test]
    public function user_without_rights_cannot_delete_timeline_row(): void
    {
        $this->actingAs(User::factory()->create());
        $timeline = Timeline::factory()->create();

        $this->delete(route('delete.timeline.row', $timeline))->assertForbidden();

        $this->assertDatabaseHas('timelines', ['id' => $timeline->id]);
    }
}
