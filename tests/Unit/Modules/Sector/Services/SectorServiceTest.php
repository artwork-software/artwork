<?php

namespace Tests\Unit\Modules\Sector\Services;

use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Sector\Services\SectorService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SectorServiceTest extends TestCase
{
    private SectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SectorService::class);
    }

    #[Test]
    public function get_all_returns_all_sectors(): void
    {
        Sector::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertCount(2, $result);
    }

    #[Test]
    public function create_new_sector_returns_unsaved_instance(): void
    {
        $sector = $this->service->createNewSector();

        $this->assertInstanceOf(Sector::class, $sector);
        $this->assertFalse($sector->exists);
    }

    #[Test]
    public function create_persists_sector_with_name_and_color(): void
    {
        $payload = new Collection([
            'name' => 'Public',
            'color' => '#001122',
        ]);

        $sector = $this->service->create($payload);

        $this->assertTrue($sector->exists);
        $this->assertSame('Public', $sector->name);
        $this->assertDatabaseHas('sectors', ['name' => 'Public', 'color' => '#001122']);
    }
}
