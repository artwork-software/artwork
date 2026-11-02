<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Artikelauswahl der Materialausgabe: zeigt unter dem Artikelnamen die Beschreibung an.
 */
final class InventoryCategoryPickerTest extends FeatureTestCase
{
    #[Test]
    public function picker_articles_contain_their_description(): void
    {
        $this->actingAsAdmin();
        $category = InventoryCategory::factory()->create();
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $category->id,
            'description' => 'Stativ mit Galgen',
        ]);

        $categories = $this->getJson(route('inventory.categories.get-all'))->assertOk()->json('categories');
        $articles = collect($categories)->firstWhere('id', $category->id)['articles'] ?? [];

        $this->assertSame('Stativ mit Galgen', collect($articles)->firstWhere('id', $article->id)['description'] ?? null);
    }
}
