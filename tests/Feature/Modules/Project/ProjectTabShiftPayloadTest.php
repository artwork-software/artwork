<?php

namespace Tests\Feature\Modules\Project;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabShiftService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Der Schichttab-Payload enthält keine „events_with_relevant“ mehr: kein Frontend las sie,
 * berechnet wurden sie aber bei jedem Aufruf (alle schichtrelevanten Termine samt Schichten
 * und Personen).
 */
final class ProjectTabShiftPayloadTest extends FeatureTestCase
{
    #[Test]
    public function shift_tab_payload_does_not_contain_events_with_relevant(): void
    {
        $this->actingAs($this->adminUser());
        $project = Project::factory()->create();
        $eventType = EventType::factory()->create();
        $project->shiftRelevantEventTypes()->attach($eventType->id);
        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $eventType->id,
        ]);

        $payload = app(ProjectTabShiftService::class)->buildShiftPayload($project);

        $this->assertArrayNotHasKey('events_with_relevant', $payload['ShiftTab']);
        foreach (
            [
                'users_for_shifts',
                'freelancers_for_shifts',
                'service_providers_for_shifts',
                'crafts',
                'current_user_crafts',
                'shift_qualifications',
                'shift_time_presets',
                'shift_sort_types',
            ] as $key
        ) {
            $this->assertArrayHasKey($key, $payload['ShiftTab']);
        }
    }
}
