<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabArtistNameService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabArtistNameServiceTest extends TestCase
{
    private ProjectTabArtistNameService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTabArtistNameService();
    }

    #[Test]
    public function build_artist_name_payload_returns_artists_field(): void
    {
        $project = Project::factory()->create(['artists' => 'Some Artist']);

        $payload = $this->service->buildArtistNamePayload($project);

        $this->assertSame(['artist_name' => 'Some Artist'], $payload);
    }
}
