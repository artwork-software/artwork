<?php

namespace Tests\Unit\Modules\ArtistResidency\Services;

use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\ArtistResidency\Services\ArtistResidencyService;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ArtistResidencyServiceTest extends TestCase
{
    private ArtistResidencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ArtistResidencyService::class);
    }

    #[Test]
    public function create_filename_combines_carbon_title_and_dpi(): void
    {
        $carbon = Carbon::parse('2025-04-15 10:30:00');

        $filename = $this->service->createFilename($carbon, 'My Project', '72');

        $this->assertSame('15.04.2025-10:30:00_My_Project_dpi_72.pdf', $filename);
    }

    #[Test]
    public function get_artist_residencies_by_project_id_returns_collection(): void
    {
        $project = Project::factory()->create();
        ArtistResidency::factory()->count(2)->create(['project_id' => $project->id]);
        ArtistResidency::factory()->create(['project_id' => null]);

        $result = $this->service->getArtistResidenciesByProjectId($project->id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(2, $result->count());
    }

    #[Test]
    public function get_artist_residencies_by_project_id_returns_empty_when_none(): void
    {
        $project = Project::factory()->create();

        $result = $this->service->getArtistResidenciesByProjectId($project->id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
}
