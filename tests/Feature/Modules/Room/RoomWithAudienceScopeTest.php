<?php

namespace Tests\Feature\Modules\Room;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projekt-Kopfzeile "Räume mit Publikum": Ein Raum zählt nur, wenn das Projekt selbst dort einen
 * Termin mit Publikum hat, nicht schon, wenn irgendein anderes Projekt dort Publikum hat.
 */
final class RoomWithAudienceScopeTest extends FeatureTestCase
{
    #[Test]
    public function only_rooms_where_the_project_itself_has_an_audience_event_are_listed(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        $roomWithOwnAudience = Room::factory()->create();
        Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $roomWithOwnAudience->id,
            'audience' => true,
        ]);

        $roomWithForeignAudience = Room::factory()->create();
        Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $roomWithForeignAudience->id,
            'audience' => false,
        ]);
        Event::factory()->create([
            'project_id' => $otherProject->id,
            'room_id' => $roomWithForeignAudience->id,
            'audience' => true,
        ]);

        $roomIds = Room::withAudience($project->id)->pluck('id')->all();

        $this->assertSame([$roomWithOwnAudience->id], $roomIds);
    }
}
