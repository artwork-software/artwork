<?php

namespace Tests\Unit\Modules\BusinessIntelligence;

use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\BusinessIntelligence\Services\BiProjectMetricsService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BiProjectMetricsServiceTest extends TestCase
{
    #[Test]
    public function capacity_is_counted_per_performance_even_when_the_room_is_reused(): void
    {
        $project = Project::factory()->create();
        $room = Room::factory()->create(['capacity' => 100]);
        BiProjectData::query()->create([
            'project_id' => $project->id,
            'sold_tickets_mode' => BiVisitorModeEnum::TOTAL,
            'sold_tickets_total' => 120,
        ]);

        Event::factory()->count(2)->sequence(
            ['start_time' => '2026-07-10 19:00:00', 'end_time' => '2026-07-10 21:00:00'],
            ['start_time' => '2026-07-11 19:00:00', 'end_time' => '2026-07-11 21:00:00'],
        )->create([
            'project_id' => $project->id,
            'room_id' => $room->id,
        ]);

        // Kapazität zählt nur Termine mit BI-Tag "Vorstellung" (kein Fallback auf alle Termine)
        $tag = BiEventTypeTag::create(['name' => 'Performance', 'name_de' => 'Vorstellung', 'color' => '#22c55e', 'kpi_role' => 'performance']);
        $tag->eventTypes()->sync(
            Event::query()->where('project_id', $project->id)->pluck('event_type_id')->unique()->all()
        );

        $project->load(['biData', 'biRoomCapacities', 'events.room', 'events.event_type.biTags']);
        $capacity = app(BiProjectMetricsService::class)->seatsCapacity(
            $project,
            Carbon::parse('2026-07-10'),
            Carbon::parse('2026-07-10'),
        );

        $this->assertSame(200, $capacity);
    }
}
