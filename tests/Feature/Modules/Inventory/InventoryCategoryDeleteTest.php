<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kategorie löschen nur, wenn sie leer ist (Entscheidung 05.10.2026). Vorher wurden enthaltene
 * Artikel am Papierkorb vorbei endgültig gelöscht.
 */
final class InventoryCategoryDeleteTest extends FeatureTestCase
{
    #[Test]
    public function a_category_with_articles_is_not_deleted(): void
    {
        $this->actingAsAdmin();
        $category = InventoryCategory::factory()->create();
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => null,
        ]);

        $this->delete(route('inventory-management.settings.categories.delete', $category))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertModelExists($category);
        $this->assertNotSoftDeleted($article);
    }

    #[Test]
    public function articles_in_the_trash_also_block_the_deletion(): void
    {
        $this->actingAsAdmin();
        $category = InventoryCategory::factory()->create();
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => null,
        ]);
        $article->delete();

        $this->delete(route('inventory-management.settings.categories.delete', $category))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
        $this->assertSoftDeleted($article);
    }

    #[Test]
    public function an_empty_category_is_deleted_with_its_sub_categories(): void
    {
        $this->actingAsAdmin();
        $category = InventoryCategory::factory()->create();
        $subCategory = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);

        $this->delete(route('inventory-management.settings.categories.delete', $category))
            ->assertRedirect()
            ->assertSessionMissing('error');

        $this->assertModelMissing($category);
        $this->assertModelMissing($subCategory);
    }
}
