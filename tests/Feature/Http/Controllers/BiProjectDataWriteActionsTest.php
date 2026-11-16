<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategoryValue;
use Artwork\Modules\BusinessIntelligence\Models\BiEventData;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\BusinessIntelligence\Models\BiSnapshot;
use Artwork\Modules\BusinessIntelligence\Models\BiTimeEffort;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Schreibende BI-Endpunkte im Projekt: Termin-Werte, Raumkapazität, Moduswechsel
 * (Besucher*innen/verkaufte Tickets), Snapshots und Zeitaufwände löschen/ändern.
 */
final class BiProjectDataWriteActionsTest extends FeatureTestCase
{
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        $this->project = Project::factory()->create();
    }

    // ---------- Termin-Werte ----------

    #[Test]
    public function event_data_is_created_once_and_then_updated_per_scope(): void
    {
        $event = Event::factory()->create(['project_id' => $this->project->id]);
        $url = route('projects.bi.upsert-event-data', [$this->project, $event]);

        $this->putJson($url, ['visitors' => 120, 'sold_tickets' => 100, 'revenue' => 1500.5])->assertOk();
        $this->putJson($url, ['visitors' => 130])->assertOk();
        $this->putJson($url, ['visitors' => 90, 'scope' => 'plan'])->assertOk();

        $actual = BiEventData::query()
            ->where(['project_id' => $this->project->id, 'event_id' => $event->id, 'scope' => 'actual'])
            ->sole();
        $this->assertSame(130, (int) $actual->visitors);
        $this->assertSame(100, (int) $actual->sold_tickets);
        $this->assertEquals(1500.5, (float) $actual->revenue);
        $this->assertSame(90, (int) BiEventData::query()
            ->where(['event_id' => $event->id, 'scope' => 'plan'])->sole()->visitors);
    }

    #[Test]
    public function event_data_for_an_event_of_another_project_is_rejected(): void
    {
        $foreignEvent = Event::factory()->create(['project_id' => Project::factory()->create()->id]);

        $this->putJson(route('projects.bi.upsert-event-data', [$this->project, $foreignEvent]), ['visitors' => 5])
            ->assertNotFound();

        $this->assertDatabaseMissing('bi_event_data', ['event_id' => $foreignEvent->id]);
    }

    #[Test]
    public function event_data_rejects_negative_and_out_of_range_values(): void
    {
        $event = Event::factory()->create(['project_id' => $this->project->id]);
        $url = route('projects.bi.upsert-event-data', [$this->project, $event]);

        $this->putJson($url, ['visitors' => -1])->assertUnprocessable()->assertJsonValidationErrors('visitors');
        $this->putJson($url, ['visitors' => 5000000000])->assertUnprocessable()->assertJsonValidationErrors('visitors');
        $this->putJson($url, ['sold_tickets' => 5000000000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sold_tickets');
        $this->putJson($url, ['revenue' => 100000000000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('revenue');
        $this->putJson($url, ['scope' => 'forecast'])->assertUnprocessable()->assertJsonValidationErrors('scope');

        $this->assertDatabaseMissing('bi_event_data', ['event_id' => $event->id]);
    }

    // ---------- Raumkapazität ----------

    #[Test]
    public function room_capacity_override_is_upserted_and_can_be_cleared(): void
    {
        $room = Room::factory()->create();
        $url = route('projects.bi.update-room-capacity', [$this->project, $room]);

        $this->putJson($url, ['capacity_override' => 250])->assertOk()->assertJsonPath('capacity_override', 250);
        $this->putJson($url, ['capacity_override' => null])->assertOk();

        $this->assertSame(1, \Artwork\Modules\BusinessIntelligence\Models\BiProjectRoomCapacity::query()
            ->where(['project_id' => $this->project->id, 'room_id' => $room->id])
            ->whereNull('capacity_override')
            ->count());
    }

    #[Test]
    public function room_capacity_rejects_negative_and_out_of_range_values(): void
    {
        $room = Room::factory()->create();
        $url = route('projects.bi.update-room-capacity', [$this->project, $room]);

        $this->putJson($url, ['capacity_override' => -5])->assertUnprocessable();
        $this->putJson($url, ['capacity_override' => 5000000000])->assertUnprocessable();

        $this->assertDatabaseMissing('bi_project_room_capacities', ['project_id' => $this->project->id]);
    }

    // ---------- Moduswechsel ----------

    #[Test]
    public function switching_visitors_to_per_event_clears_the_total_and_back_clears_event_values(): void
    {
        $event = Event::factory()->create(['project_id' => $this->project->id]);
        BiProjectData::query()->create([
            'project_id' => $this->project->id,
            'scope' => 'actual',
            'visitor_mode' => 'total',
            'visitors_total' => 500,
        ]);

        $this->putJson(route('projects.bi.switch-visitor-mode', $this->project), ['mode' => 'per_event'])
            ->assertOk()
            ->assertJsonPath('visitor_mode', 'per_event')
            ->assertJsonPath('visitors_total', null);

        BiEventData::query()->create([
            'project_id' => $this->project->id,
            'event_id' => $event->id,
            'scope' => 'actual',
            'visitors' => 40,
            'sold_tickets' => 30,
        ]);

        $this->putJson(route('projects.bi.switch-visitor-mode', $this->project), ['mode' => 'total'])
            ->assertOk()
            ->assertJsonPath('visitor_mode', 'total');

        $eventData = BiEventData::query()->where('event_id', $event->id)->sole();
        $this->assertNull($eventData->visitors);
        // Verkaufte Tickets hängen nicht am Besucher*innen-Modus
        $this->assertSame(30, (int) $eventData->sold_tickets);
    }

    #[Test]
    public function switching_to_the_current_visitor_mode_changes_nothing(): void
    {
        BiProjectData::query()->create([
            'project_id' => $this->project->id,
            'scope' => 'actual',
            'visitor_mode' => 'total',
            'visitors_total' => 500,
        ]);

        $this->putJson(route('projects.bi.switch-visitor-mode', $this->project), ['mode' => 'total'])
            ->assertOk()
            ->assertJsonPath('visitors_total', 500);
    }

    #[Test]
    public function switching_modes_requires_a_valid_mode(): void
    {
        $this->putJson(route('projects.bi.switch-visitor-mode', $this->project), ['mode' => 'weekly'])
            ->assertUnprocessable();
        $this->putJson(route('projects.bi.switch-sold-tickets-mode', $this->project), [])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('bi_project_data', ['project_id' => $this->project->id]);
    }

    #[Test]
    public function switching_sold_tickets_to_total_drops_per_event_values_and_category_values(): void
    {
        $event = Event::factory()->create(['project_id' => $this->project->id]);
        $category = BiAudienceCategory::factory()->create(['pricing_type' => 'full', 'position' => 1]);
        BiProjectData::query()->create([
            'project_id' => $this->project->id,
            'scope' => 'actual',
            'sold_tickets_mode' => 'per_event',
        ]);
        BiEventData::query()->create([
            'project_id' => $this->project->id,
            'event_id' => $event->id,
            'scope' => 'actual',
            'sold_tickets' => 80,
            'visitors' => 75,
        ]);
        $perEvent = BiAudienceCategoryValue::factory()->create([
            'project_id' => $this->project->id,
            'bi_audience_category_id' => $category->id,
            'event_id' => $event->id,
            'scope' => 'actual',
            'quantity' => 10,
        ]);
        $planPerEvent = BiAudienceCategoryValue::factory()->create([
            'project_id' => $this->project->id,
            'bi_audience_category_id' => $category->id,
            'event_id' => $event->id,
            'scope' => 'plan',
            'quantity' => 12,
        ]);

        $this->putJson(route('projects.bi.switch-sold-tickets-mode', $this->project), ['mode' => 'total'])
            ->assertOk()
            ->assertJsonPath('sold_tickets_mode', 'total');

        $eventData = BiEventData::query()->where('event_id', $event->id)->sole();
        $this->assertNull($eventData->sold_tickets);
        $this->assertSame(75, (int) $eventData->visitors);
        $this->assertDatabaseMissing('bi_audience_category_values', ['id' => $perEvent->id]);
        // Werte des anderen Scopes (Plan) bleiben unberührt
        $this->assertDatabaseHas('bi_audience_category_values', ['id' => $planPerEvent->id]);
    }

    #[Test]
    public function switching_sold_tickets_to_per_event_drops_the_total_and_total_category_values(): void
    {
        $category = BiAudienceCategory::factory()->create(['pricing_type' => 'reduced', 'position' => 1]);
        BiProjectData::query()->create([
            'project_id' => $this->project->id,
            'scope' => 'actual',
            'sold_tickets_mode' => 'total',
            'sold_tickets_total' => 900,
        ]);
        $totalValue = BiAudienceCategoryValue::factory()->create([
            'project_id' => $this->project->id,
            'bi_audience_category_id' => $category->id,
            'event_id' => null,
            'scope' => 'actual',
            'quantity' => 100,
        ]);

        $this->putJson(route('projects.bi.switch-sold-tickets-mode', $this->project), ['mode' => 'per_event'])
            ->assertOk()
            ->assertJsonPath('sold_tickets_mode', 'per_event')
            ->assertJsonPath('sold_tickets_total', null);

        $this->assertDatabaseMissing('bi_audience_category_values', ['id' => $totalValue->id]);
    }

    // ---------- Snapshots ----------

    #[Test]
    public function a_snapshot_of_the_project_can_be_deleted(): void
    {
        $snapshot = BiSnapshot::factory()->create(['project_id' => $this->project->id]);
        $other = BiSnapshot::factory()->create(['project_id' => $this->project->id]);

        $this->deleteJson(route('projects.bi.snapshots.destroy', [$this->project, $snapshot]))->assertNoContent();

        $this->assertDatabaseMissing('bi_snapshots', ['id' => $snapshot->id]);
        $this->assertDatabaseHas('bi_snapshots', ['id' => $other->id]);
    }

    #[Test]
    public function a_snapshot_of_another_project_cannot_be_deleted_via_this_project(): void
    {
        $foreign = BiSnapshot::factory()->create();

        $this->deleteJson(route('projects.bi.snapshots.destroy', [$this->project, $foreign]))->assertNotFound();

        $this->assertDatabaseHas('bi_snapshots', ['id' => $foreign->id]);
    }

    // ---------- Zeitaufwände ----------

    #[Test]
    public function a_time_effort_can_be_updated(): void
    {
        $effort = BiTimeEffort::factory()->create(['project_id' => $this->project->id, 'effort_bucket' => '0-10']);

        $this->putJson(route('projects.bi.time-efforts.update', [$this->project, $effort]), [
            'label' => 'Technik',
            'effort_bucket' => '25-50',
        ])->assertOk()->assertJsonPath('label', 'Technik')->assertJsonPath('effort_bucket', '25-50');

        $this->assertDatabaseHas('bi_time_efforts', ['id' => $effort->id, 'label' => 'Technik', 'effort_bucket' => '25-50']);
    }

    #[Test]
    public function updating_a_time_effort_validates_the_bucket_and_the_project(): void
    {
        $effort = BiTimeEffort::factory()->create(['project_id' => $this->project->id, 'label' => 'Alt']);
        $foreign = BiTimeEffort::factory()->create(['label' => 'Fremd']);

        $this->putJson(route('projects.bi.time-efforts.update', [$this->project, $effort]), [
            'label' => 'Neu',
            'effort_bucket' => '1000',
        ])->assertUnprocessable()->assertJsonValidationErrors('effort_bucket');

        $this->putJson(route('projects.bi.time-efforts.update', [$this->project, $foreign]), [
            'label' => 'Gekapert',
            'effort_bucket' => '0-10',
        ])->assertNotFound();

        $this->assertSame('Alt', $effort->fresh()->label);
        $this->assertSame('Fremd', $foreign->fresh()->label);
    }

    #[Test]
    public function a_time_effort_can_be_deleted_but_not_via_another_project(): void
    {
        $effort = BiTimeEffort::factory()->create(['project_id' => $this->project->id]);
        $foreign = BiTimeEffort::factory()->create();

        $this->deleteJson(route('projects.bi.time-efforts.destroy', [$this->project, $effort]))->assertNoContent();
        $this->deleteJson(route('projects.bi.time-efforts.destroy', [$this->project, $foreign]))->assertNotFound();

        $this->assertDatabaseMissing('bi_time_efforts', ['id' => $effort->id]);
        $this->assertDatabaseHas('bi_time_efforts', ['id' => $foreign->id]);
    }
}
