<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Services\ProjectTabDocumentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabDocumentServiceTest extends TestCase
{
    private ProjectTabDocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTabDocumentService();
    }

    #[Test]
    public function build_document_payload_returns_empty_collection_without_scope(): void
    {
        $project = Project::factory()->create();
        ProjectFile::factory()->create(['project_id' => $project->id]);

        $payload = $this->service->buildDocumentPayload($project);

        $this->assertArrayHasKey('documents', $payload);
        $this->assertCount(0, $payload['documents']);
    }

    #[Test]
    public function build_all_documents_payload_returns_files_without_user(): void
    {
        $project = Project::factory()->create();
        ProjectFile::factory()->count(2)->create(['project_id' => $project->id]);

        $payload = $this->service->buildAllDocumentsPayload($project);

        $this->assertGreaterThanOrEqual(2, count($payload['documents']));
        $this->assertArrayHasKey('hiddenTabNames', $payload);
        $this->assertSame([], $payload['hiddenTabNames']);
    }
}
