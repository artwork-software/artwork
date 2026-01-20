<?php

namespace Tests\Unit\Artwork\Modules\Project\DTOs;

use Artwork\Modules\Project\DTOs\ProjectSearchDTO;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Event\Models\Event;
use Tests\TestCase;
use Carbon\Carbon;

class ProjectSearchDTOTest extends TestCase
{
    public function test_from_model_with_dates(): void
    {
        $project = Project::factory()->create(['name' => 'Test Project']);

        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => '2026-01-01 10:00:00',
            'end_time' => '2026-01-01 12:00:00',
        ]);

        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => '2026-01-10 14:00:00',
            'end_time' => '2026-01-10 16:00:00',
        ]);

        $dto = ProjectSearchDTO::fromModel($project);

        $this->assertEquals($project->id, $dto->id);
        $this->assertEquals('Test Project', $dto->name);
        $this->assertEquals('01.01.2026 10:00', $dto->first_event_date);
        $this->assertEquals('10.01.2026 16:00', $dto->last_event_date);
    }

    public function test_from_model_without_dates(): void
    {
        $project = Project::factory()->create(['name' => 'Empty Project']);

        $dto = ProjectSearchDTO::fromModel($project);

        $this->assertEquals($project->id, $dto->id);
        $this->assertEquals('Empty Project', $dto->name);
        $this->assertNull($dto->first_event_date);
        $this->assertNull($dto->last_event_date);
    }
}
