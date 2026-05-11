<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Services\ProjectStateService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectStateServiceTest extends TestCase
{
    private ProjectStateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectStateService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function get_all_returns_created_states(): void
    {
        ProjectState::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(2, $result->count());
        $this->assertContainsOnlyInstancesOf(ProjectState::class, $result);
    }
}
