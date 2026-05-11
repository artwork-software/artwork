<?php

namespace Tests\Unit\Modules\MaterialSet\Services;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\MaterialSet\Http\Requests\StoreMaterialSetRequest;
use Artwork\Modules\MaterialSet\Http\Requests\UpdateMaterialSetRequest;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\MaterialSet\Services\MaterialSetService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MaterialSetServiceTest extends TestCase
{
    private MaterialSetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MaterialSetService::class);
    }

    private function buildStoreRequest(array $data): StoreMaterialSetRequest
    {
        $request = StoreMaterialSetRequest::create('/material-sets', 'POST', $data);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();
        return $request;
    }

    private function buildUpdateRequest(array $data): UpdateMaterialSetRequest
    {
        $request = UpdateMaterialSetRequest::create('/material-sets', 'PATCH', $data);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();
        return $request;
    }

    #[Test]
    public function store_creates_material_set_with_items(): void
    {
        $article = InventoryArticle::factory()->create();
        $request = $this->buildStoreRequest([
            'name' => 'My set',
            'description' => 'A description',
            'items' => [
                ['id' => $article->id, 'quantity' => 5],
            ],
        ]);

        $set = $this->service->store($request);

        $this->assertInstanceOf(MaterialSet::class, $set);
        $this->assertDatabaseHas('material_sets', ['name' => 'My set']);
        $this->assertDatabaseHas('material_set_items', [
            'material_set_id' => $set->id,
            'inventory_article_id' => $article->id,
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function update_replaces_items(): void
    {
        $article1 = InventoryArticle::factory()->create();
        $article2 = InventoryArticle::factory()->create();
        $createRequest = $this->buildStoreRequest([
            'name' => 'Old',
            'description' => null,
            'items' => [
                ['id' => $article1->id, 'quantity' => 3],
            ],
        ]);
        $set = $this->service->store($createRequest);

        $updateRequest = $this->buildUpdateRequest([
            'name' => 'New',
            'description' => 'Updated',
            'items' => [
                ['id' => $article2->id, 'quantity' => 7],
            ],
        ]);
        $updated = $this->service->update($set, $updateRequest);

        $this->assertSame('New', $updated->name);
        $this->assertDatabaseMissing('material_set_items', [
            'material_set_id' => $set->id,
            'inventory_article_id' => $article1->id,
        ]);
        $this->assertDatabaseHas('material_set_items', [
            'material_set_id' => $set->id,
            'inventory_article_id' => $article2->id,
            'quantity' => 7,
        ]);
    }

    #[Test]
    public function delete_removes_set_and_items(): void
    {
        $article = InventoryArticle::factory()->create();
        $request = $this->buildStoreRequest([
            'name' => 'To delete',
            'items' => [
                ['id' => $article->id, 'quantity' => 2],
            ],
        ]);
        $set = $this->service->store($request);

        $this->service->delete($set);

        $this->assertDatabaseMissing('material_sets', ['id' => $set->id]);
        $this->assertDatabaseMissing('material_set_items', ['material_set_id' => $set->id]);
    }
}
