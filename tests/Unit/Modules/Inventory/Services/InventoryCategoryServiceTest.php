<?php

namespace Tests\Unit\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Services\InventoryCategoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InventoryCategoryServiceTest extends TestCase
{
    private InventoryCategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryCategoryService::class);
    }

    #[Test]
    public function get_all_with_relations_returns_collection(): void
    {
        InventoryCategory::factory()->count(2)->create();

        $result = $this->service->getAllWithRelations();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function paginate_with_relations_returns_paginator(): void
    {
        InventoryCategory::factory()->count(3)->create();

        $result = $this->service->paginateWithRelations(50);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertGreaterThanOrEqual(3, $result->total());
    }

    #[Test]
    public function create_with_relations_persists_category(): void
    {
        $category = $this->service->createWithRelations(
            ['name' => 'New cat'],
            collect(),
            collect()
        );

        $this->assertSame('New cat', $category->name);
        $this->assertDatabaseHas('inventory_categories', ['name' => 'New cat']);
    }

    #[Test]
    public function update_with_relations_modifies_category(): void
    {
        $category = InventoryCategory::factory()->create(['name' => 'Old']);

        $updated = $this->service->updateWithRelations(
            $category,
            ['name' => 'Updated'],
            collect(),
            collect()
        );

        $this->assertSame('Updated', $updated->name);
    }

    #[Test]
    public function paginate_for_api_delegates_to_paginate_with_relations(): void
    {
        InventoryCategory::factory()->count(2)->create();

        $paginator = $this->service->paginateForApi(25);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
    }
}
