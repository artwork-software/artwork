<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Events\CreatedShiftInShiftPlan;
use Artwork\Modules\Shift\Events\UpdateEventShiftInShiftPlan;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression: Nach einem Projektwechsel im Schicht-Modal erschien die Schicht im Dienstplan bis zum
 * Neuladen als "ohne Projekt" — der Broadcast lieferte ein ggf. veraltet geladenes Projekt und keine
 * Lookup-Einträge für ein Projekt, das der Client noch nicht kannte.
 */
class ShiftBroadcastProjectLookupTest extends TestCase
{
    #[Test]
    public function update_broadcast_uses_the_new_project_even_if_the_relation_was_loaded_before(): void
    {
        $shift = Shift::factory()->create(['project_id' => null]);
        $shift->load('project'); // alte (leere) Relation gecached, wie im Controller-Ablauf möglich

        $project = Project::factory()->create();
        $shift->project_id = $project->id;
        $shift->save();

        $payload = (new UpdateShiftInShiftPlan($shift, (int) ($shift->room_id ?? 1)))->broadcastWith();

        $this->assertSame($project->id, $payload['shift']->projectId);
        $this->assertSame($project->name, $payload['shift']->projectName);
        $this->assertArrayHasKey($project->id, $payload['lookups']['projectsById']);
        $this->assertSame($project->name, $payload['lookups']['projectsById'][$project->id]['name']);
        $this->assertArrayHasKey($shift->craft_id, $payload['lookups']['craftsById']);
    }

    #[Test]
    public function create_and_event_update_broadcasts_ship_project_lookups(): void
    {
        $project = Project::factory()->create();
        $shift = Shift::factory()->create(['project_id' => $project->id]);

        foreach ([CreatedShiftInShiftPlan::class, UpdateEventShiftInShiftPlan::class] as $eventClass) {
            $payload = (new $eventClass($shift, 1))->broadcastWith();

            $this->assertSame($project->id, $payload['shift']->projectId, $eventClass);
            $this->assertArrayHasKey($project->id, $payload['lookups']['projectsById'], $eventClass);
        }
    }

    #[Test]
    public function broadcast_without_project_has_empty_project_lookup(): void
    {
        $shift = Shift::factory()->create(['project_id' => null]);

        $payload = (new UpdateShiftInShiftPlan($shift, 1))->broadcastWith();

        $this->assertNull($payload['shift']->projectId);
        $this->assertSame([], $payload['lookups']['projectsById']);
    }
}
