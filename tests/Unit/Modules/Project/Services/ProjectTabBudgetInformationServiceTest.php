<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabBudgetInformationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabBudgetInformationServiceTest extends TestCase
{
    private ProjectTabBudgetInformationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectTabBudgetInformationService::class);
    }

    #[Test]
    public function build_budget_information_payload_wraps_dto_under_top_level_key(): void
    {
        $project = Project::factory()->create();

        $payload = $this->service->buildBudgetInformationPayload($project);

        $this->assertArrayHasKey('BudgetInformation', $payload);
        $this->assertIsArray($payload['BudgetInformation']);
    }
}
