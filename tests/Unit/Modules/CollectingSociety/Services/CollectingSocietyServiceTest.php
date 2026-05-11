<?php

namespace Tests\Unit\Modules\CollectingSociety\Services;

use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CollectingSociety\Services\CollectingSocietyService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CollectingSocietyServiceTest extends TestCase
{
    private CollectingSocietyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CollectingSocietyService::class);
    }

    #[Test]
    public function get_all_returns_empty_collection(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function get_all_returns_all_collecting_societies(): void
    {
        CollectingSociety::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertCount(2, $result);
    }
}
