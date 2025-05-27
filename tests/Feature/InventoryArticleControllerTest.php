<?php

namespace Tests\Feature;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryArticleControllerTest extends TestCase
{
    use RefreshDatabase;

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

        $data = [
            'name' => 'Testartikel',
            'description' => 'Beschreibung',
            'inventory_category_id' => $category->id,
            'quantity' => 10,
            'is_detailed_quantity' => false,
            'main_image_index' => 0, // Pflichtfeld laut Fehlermeldung
        ];

        $response = $this->post(route('inventory-management.articles.store'), $data);
        $response->assertSessionHasNoErrors();
        $response->assertStatus(200); // Redirect nach Erfolg
        $this->assertDatabaseHas('inventory_articles', ['name' => 'Testartikel']);
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

        $response = $this->get(route('inventory.articles.available-stock', [$article->id, '2025-01-01', '2025-01-10']));
        $response->assertStatus(200);
        $response->assertJsonStructure(['availableStock']);
    }
}

