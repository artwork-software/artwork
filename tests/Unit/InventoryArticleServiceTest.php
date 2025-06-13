<?php

namespace Tests\Unit;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Request;
use Mockery;
use Tests\TestCase;

class InventoryArticleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_article(): void
    {
        $repo = Mockery::mock(InventoryArticleRepository::class);
        $createdArticle = new InventoryArticle(['name' => 'Artikel']);
        $repo->shouldReceive('create')->once()->andReturn($createdArticle);
        $repo->shouldReceive('addImages')->andReturnUsing(function ($article) {
            // Simuliere das Verhalten von addImages, gebe das Article-Objekt zurück
            return $article;
        });
        $repo->shouldReceive('attachProperties')->withArgs(function ($article) use ($createdArticle) {
            return $article instanceof InventoryArticle && $article->name === $createdArticle->name;
        });
        $repo->shouldReceive('addDetailedArticles');
        $repo->shouldReceive('attachStatusValues');

        $service = new InventoryArticleService($repo);
        $request = StoreInventoryArticleRequest::create('/', 'POST', [
            'name' => 'Artikel',
            'description' => 'Beschreibung',
            'inventory_category_id' => 1,
            'quantity' => 5,
            'is_detailed_quantity' => false,
        ]);
        $result = $service->store($request);
        $this->assertInstanceOf(InventoryArticle::class, $result);
        $this->assertEquals('Artikel', $result->name);
    }

    public function test_get_available_stock_returns_array(): void
    {
        $repo = Mockery::mock(InventoryArticleRepository::class);
        $article = Mockery::mock(InventoryArticle::class);
        $repo->shouldReceive('getAvailableStock')->andReturn(['available' => 3, 'total' => 5, 'reserved' => 2]);
        $service = new InventoryArticleService($repo);
        $result = $service->getAvailableStock($article, '2025-01-01', '2025-01-10');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
    }
}
