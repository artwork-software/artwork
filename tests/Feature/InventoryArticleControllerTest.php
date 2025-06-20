<?php

namespace Tests\Feature;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class InventoryArticleControllerTest extends TestCase
{
    public function test_index_returns_successful_response()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        // Notwendige Kategorie anlegen, damit Service nicht fehlschlägt
        $category = \Artwork\Modules\Inventory\Models\InventoryCategory::factory()->create();
        // Notwendigen Filter für den User anlegen
        \Artwork\Modules\User\Models\UserInventoryArticlePlanFilter::create([
            'user_id' => $user->id,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        // Mock the InventoryPlanningService to avoid issues with missing data
        $this->mock(\Artwork\Modules\Inventory\Services\InventoryPlanningService::class, function ($mock) {
            $mock->shouldReceive('getAvailabilityData')->andReturn([
                'articles' => [],
                'categories' => [],
                'subCategories' => [],
                'dateRange' => [],
                'filter' => []
            ]);
        });

        $response = $this->get(route('inventory-management.article.planning'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Inventory/InventoryArticlePlanning')
        );
    }

    public function test_store_creates_article()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Notwendige Kategorie anlegen
        $category = \Artwork\Modules\Inventory\Models\InventoryCategory::factory()->create();

        \Artwork\Modules\User\Models\UserInventoryArticlePlanFilter::create([
            'user_id' => $user->id,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        // Mock the InventoryArticleService to verify it's called correctly
        $this->mock(\Artwork\Modules\Inventory\Services\InventoryArticleService::class, function ($mock) {
            $mock->shouldReceive('store')->once()->andReturn(new \Artwork\Modules\Inventory\Models\InventoryArticle());
        });

        $data = [
            'name' => 'Testartikel',
            'description' => 'Beschreibung',
            'inventory_category_id' => $category->id,
            'quantity' => 10,
            'is_detailed_quantity' => false,
            'main_image_index' => 0, // Pflichtfeld laut Fehlermeldung
            'newImages' => [], // Add empty newImages array to match what the service expects
            'properties' => [], // Add empty properties array to match what the service expects
            'detailed_article_quantities' => [], // Add empty detailed_article_quantities array to match what the service expects
            'statusValues' => [], // Add empty statusValues array to match what the service expects
        ];

        $response = $this->post(route('inventory-management.articles.store'), $data);
        $response->assertSessionHasNoErrors();
        // The controller returns a 200 OK status by default
        $response->assertStatus(200);
    }

    public function test_available_stock_returns_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = \Artwork\Modules\Inventory\Models\InventoryCategory::factory()->create();
        $article = InventoryArticle::factory()->create([
            'quantity' => 5,
            'inventory_category_id' => $category->id,
        ]);

        // Mock the InventoryArticleService to return a predictable result
        $this->mock(\Artwork\Modules\Inventory\Services\InventoryArticleService::class, function ($mock) {
            $mock->shouldReceive('getAvailableStock')->andReturn([
                'available' => 3,
                'total' => 5,
                'reserved' => 2
            ]);
        });

        $response = $this->get(route('inventory.articles.available-stock', [$article->id, '2025-01-01', '2025-01-10']));
        $response->assertStatus(200);
        $response->assertJsonStructure(['availableStock']);
        $response->assertJson([
            'availableStock' => [
                'available' => 3,
                'total' => 5,
                'reserved' => 2
            ]
        ]);
    }
}
