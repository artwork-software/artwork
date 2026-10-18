<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Seitenleiste und Breadcrumb brauchen je Kategorie/Unterkategorie nur Anzahlen; der komplette
 * Artikelbaum (inkl. Eigenschaften) wurde früher auf jedem Inventar-Aufruf mitgeschickt.
 */
final class InventoryIndexPayloadTest extends FeatureTestCase
{
    #[Test]
    public function the_category_tree_carries_article_counts_instead_of_articles(): void
    {
        $this->actingAsAdmin();
        $category = InventoryCategory::factory()->create(['name' => 'AAA Licht']);
        $subCategory = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);
        InventoryArticle::factory()->count(3)->create([
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($category, $subCategory): void {
                $tree = collect($page->toArray()['props']['categories'])->keyBy('id');
                $node = $tree->get($category->id);
                $this->assertSame(3, $node['articles_count']);
                $this->assertArrayNotHasKey('articles', $node);
                $subNode = collect($node['subcategories'])->keyBy('id')->get($subCategory->id);
                $this->assertSame(3, $subNode['articles_count']);
                $this->assertArrayNotHasKey('articles', $subNode);
            });

        $this->get(route('inventory.category.show', ['inventoryCategory' => $category->id]))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($subCategory): void {
                $current = $page->toArray()['props']['currentCategory'];
                $subNode = collect($current['subcategories'])->keyBy('id')->get($subCategory->id);
                $this->assertSame(3, $subNode['articles_count']);
                $this->assertArrayNotHasKey('articles', $subNode);
            });
    }

    #[Test]
    public function article_counts_respect_active_filters(): void
    {
        $this->actingAsAdmin();
        $category = InventoryCategory::factory()->create();
        $subCategory = InventorySubCategory::factory()->create(['inventory_category_id' => $category->id]);
        InventoryArticle::factory()->create([
            'name' => 'Scheinwerfer Zählprobe',
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);
        InventoryArticle::factory()->create([
            'name' => 'Kabeltrommel',
            'inventory_category_id' => $category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);

        $this->get(route('inventory.index', ['search' => '*Zählprobe*']))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($category): void {
                $node = collect($page->toArray()['props']['categories'])->keyBy('id')->get($category->id);
                $this->assertSame(1, $node['articles_count']);
            });
    }
}
