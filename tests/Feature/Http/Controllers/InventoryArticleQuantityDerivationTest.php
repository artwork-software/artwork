<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Services\TypeNumberGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Bei Einzelinventar-Artikeln ist die Gesamtmenge IMMER die Summe der Einzelbestände
 * (Abnahme MAT-05 Ref. 1.24) — auch die Inline-Save-Endpunkte müssen die Invariante
 * halten: der Einzelbestand-Save berechnet die Summe neu, der direkte quantity-Write
 * am Artikel wird abgelehnt.
 */
final class InventoryArticleQuantityDerivationTest extends FeatureTestCase
{
    private InventoryArticle $article;
    private InventoryDetailedQuantityArticle $detail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();

        $category = InventoryCategory::factory()->create();
        $status = InventoryArticleStatus::query()->create([
            'name' => 'Einsatzbereit',
            'color' => '#10b981',
            'order' => 1,
        ]);

        $this->article = InventoryArticle::factory()->create([
            'name' => 'Stuhl',
            'inventory_category_id' => $category->id,
            'is_detailed_quantity' => true,
            'quantity' => 3,
        ]);

        $this->detail = $this->article->detailedArticleQuantities()->create([
            'name' => 'Stuhl A',
            'quantity' => 1,
            'inventory_article_status_id' => $status->id,
            'detail_number' => 1,
            'external_id' => TypeNumberGenerator::generateDetailExternalId($this->article->external_id, 1),
            'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber(
                $this->article->inventory_number,
                1
            ),
        ]);
        $this->article->detailedArticleQuantities()->create([
            'name' => 'Stuhl B',
            'quantity' => 2,
            'inventory_article_status_id' => $status->id,
            'detail_number' => 2,
            'external_id' => TypeNumberGenerator::generateDetailExternalId($this->article->external_id, 2),
            'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber(
                $this->article->inventory_number,
                2
            ),
        ]);
    }

    #[Test]
    public function an_inline_detail_quantity_save_recalculates_the_article_total(): void
    {
        $this->patchJson(
            route('inventory-management.articles.detailed.update-field', $this->detail),
            ['field' => 'quantity', 'value' => 5]
        )->assertSuccessful();

        // 5 (Stuhl A) + 2 (Stuhl B)
        $this->assertSame(7, $this->article->fresh()->quantity);
    }

    #[Test]
    public function a_direct_quantity_write_on_a_detailed_article_is_rejected(): void
    {
        $this->patchJson(
            route('inventory-management.articles.update-field', $this->article),
            ['field' => 'quantity', 'value' => 999]
        )->assertUnprocessable();

        $this->assertSame(3, $this->article->fresh()->quantity);
    }

    #[Test]
    public function a_direct_quantity_write_on_a_simple_article_still_works(): void
    {
        $simple = InventoryArticle::factory()->create([
            'name' => 'Tisch',
            'inventory_category_id' => $this->article->inventory_category_id,
            'is_detailed_quantity' => false,
            'quantity' => 4,
        ]);

        $this->patchJson(
            route('inventory-management.articles.update-field', $simple),
            ['field' => 'quantity', 'value' => 9]
        )->assertSuccessful();

        $this->assertSame(9, $simple->fresh()->quantity);
    }
}
