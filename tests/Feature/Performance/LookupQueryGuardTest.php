<?php

namespace Tests\Feature\Performance;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Wächter gegen wieder einreißende Abfragen je Zeile bzw. unnötig berechnete Seiten-Props.
 */
final class LookupQueryGuardTest extends FeatureTestCase
{
    #[Test]
    public function project_search_does_not_query_the_period_per_hit(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $this->projectWithEvent('Suchwaechter Alpha', $room);

        $single = $this->countQueries(
            fn () => $this->getJson(route('projects.search', ['query' => 'Suchwaechter']))->assertOk()
        );

        foreach (['Beta', 'Gamma', 'Delta', 'Epsilon'] as $suffix) {
            $this->projectWithEvent('Suchwaechter ' . $suffix, $room);
        }

        $response = null;
        $many = $this->countQueries(function () use (&$response): void {
            $response = $this->getJson(route('projects.search', ['query' => 'Suchwaechter']))->assertOk();
        });

        $this->assertCount(5, $response->json());
        $this->assertNotNull($response->json('0.first_event_date'));
        // Die erste Anfrage lädt zusätzlich Rechte; mehr Treffer dürfen keine weiteren Abfragen kosten
        $this->assertLessThanOrEqual($single, $many);
    }

    #[Test]
    public function room_page_lists_only_open_requests_as_event_requests(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        Event::factory()->count(3)->create(['room_id' => $room->id, 'occupancy_option' => false]);
        $request = Event::factory()->create(['room_id' => $room->id, 'occupancy_option' => true]);

        $this->get(route('rooms.show', $room))->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('room.event_requests', 1)
                ->where('room.event_requests.0.id', $request->id)
        );
    }

    #[Test]
    public function inventory_basket_reload_skips_master_data(): void
    {
        $this->actingAsAdmin();
        $version = $this->get(route('inventory.index'))->assertOk()->viewData('page')['version'] ?? '';

        $full = $this->countQueries(fn () => $this->get(route('inventory.index'))->assertOk());
        $partial = $this->countQueries(fn () => $this->get(route('inventory.index'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Inertia-Partial-Component' => 'Inventory/Index',
            'X-Inertia-Partial-Data' => 'productBaskets',
        ])->assertOk()->assertJsonStructure(['props' => ['productBaskets']]));

        $this->assertLessThanOrEqual($full - 8, $partial);
    }

    private function projectWithEvent(string $name, Room $room): Project
    {
        $project = Project::factory()->create(['name' => $name]);
        Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_time' => Carbon::parse('2026-03-01 10:00'),
            'end_time' => Carbon::parse('2026-03-01 12:00'),
        ]);

        return $project;
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
