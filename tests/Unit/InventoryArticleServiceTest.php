<?php

namespace Tests\Unit;

use Artwork\Modules\Inventory\Http\Requests\StoreInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Illuminate\Support\Facades\Request;
use Mockery;
use Tests\TestCase;

class InventoryArticleServiceTest extends TestCase
{
    public function test_store_creates_article()
    {
        $repo = Mockery::mock(InventoryArticleRepository::class);

        // Create a proper mock for the InventoryArticle class
        $createdArticle = Mockery::mock(InventoryArticle::class);
        $createdArticle->shouldReceive('getAttribute')->with('name')->andReturn('Artikel');

        // Set up the load method to return the article itself
        $createdArticle->shouldReceive('load')->with(['properties', 'images', 'statusValues'])->andReturn($createdArticle);

        // Mock DB transaction
        \Illuminate\Support\Facades\DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $repo->shouldReceive('create')->once()->andReturn($createdArticle);

        // Mock repository methods that are called by the service's helper methods
        $repo->shouldReceive('addImages')->andReturnUsing(function($article, $images, $mainImageIndex) {
            return $article;
        });

        $repo->shouldReceive('attachProperties')->withArgs(function($article, $properties) use ($createdArticle) {
            return $article === $createdArticle;
        });

        $repo->shouldReceive('addDetailedArticles');
        $repo->shouldReceive('attachStatusValues');

        $service = new InventoryArticleService($repo);
        $request = StoreInventoryArticleRequest::create('/','POST', [
            'name' => 'Artikel',
            'description' => 'Beschreibung',
            'inventory_category_id' => 1,
            'quantity' => 5,
            'is_detailed_quantity' => false,
            'statusValues' => [],
            'newImages' => [], // Add empty newImages array to match what the service expects
            'properties' => [], // Add empty properties array to match what the service expects
            'detailed_article_quantities' => [], // Add empty detailed_article_quantities array to match what the service expects
        ]);

        $result = $service->store($request);
        $this->assertSame($createdArticle, $result);
    }

    public function test_get_available_stock_returns_array()
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
