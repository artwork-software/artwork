<?php

/**
 * Regression-Tests für den Match-and-Update-Flow von DetailArticles
 * (siehe InventoryArticleService::syncDetailedArticles).
 */

namespace Tests\Feature;

use Artwork\Modules\Inventory\Http\Requests\UpdateInventoryArticleRequest;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\Inventory\Services\TypeNumberGenerator;
use PHPUnit\Framework\Attributes\Test;

final class InventoryArticleSyncDetailedArticlesTest extends FeatureTestCase
{
    private InventoryCategory $category;
    private InventoryArticleStatus $status;
    private InventoryArticleProperties $property;
    private InventoryArticle $article;
    private InventoryDetailedQuantityArticle $detail1;
    private InventoryDetailedQuantityArticle $detail2;
    private InventoryDetailedQuantityArticle $detail3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();

        $this->category = InventoryCategory::factory()->create();

        $this->status = InventoryArticleStatus::query()->create([
            'name' => 'Einsatzbereit',
            'color' => '#10b981',
            'order' => 1,
        ]);

        $this->property = InventoryArticleProperties::query()->create([
            'name' => 'Material',
            'type' => 'string',
        ]);

        $this->article = InventoryArticle::factory()->create([
            'name' => 'Stuhl',
            'inventory_category_id' => $this->category->id,
            'is_detailed_quantity' => true,
            'quantity' => 3,
        ]);

        // Both external_id and inventory_number are auto-generated via model boot
        $mainExternalId = $this->article->external_id;
        $mainInventoryNumber = $this->article->inventory_number;

        $this->detail1 = $this->article->detailedArticleQuantities()->create([
            'name' => 'Stuhl A',
            'description' => 'erster',
            'quantity' => 1,
            'inventory_article_status_id' => $this->status->id,
            'detail_number' => 1,
            'external_id' => TypeNumberGenerator::generateDetailExternalId($mainExternalId, 1),
            'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($mainInventoryNumber, 1),
        ]);
        $this->detail2 = $this->article->detailedArticleQuantities()->create([
            'name' => 'Stuhl B',
            'description' => 'zweiter',
            'quantity' => 1,
            'inventory_article_status_id' => $this->status->id,
            'detail_number' => 2,
            'external_id' => TypeNumberGenerator::generateDetailExternalId($mainExternalId, 2),
            'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($mainInventoryNumber, 2),
        ]);
        $this->detail3 = $this->article->detailedArticleQuantities()->create([
            'name' => 'Stuhl C',
            'description' => 'dritter',
            'quantity' => 1,
            'inventory_article_status_id' => $this->status->id,
            'detail_number' => 3,
            'external_id' => TypeNumberGenerator::generateDetailExternalId($mainExternalId, 3),
            'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($mainInventoryNumber, 3),
        ]);
    }

    /**
     * Baut einen Eintrag für den `detailed_article_quantities`-Payload.
     *
     * @param array<int, mixed> $properties
     * @return array<string, mixed>
     */
    private function detailPayload(
        ?int $id,
        string $name,
        string $description = '',
        int $quantity = 1,
        array $properties = [],
        ?int $statusId = null,
    ): array {
        $statusId ??= $this->status->id;

        $entry = [
            'name' => $name,
            'description' => $description,
            'quantity' => $quantity,
            'properties' => $properties,
            'status' => ['id' => $statusId],
        ];

        if ($id !== null) {
            $entry['id'] = $id;
        }

        return $entry;
    }

    /**
     * Hilfsfunktion: Baut einen UpdateInventoryArticleRequest mit dem gegebenen DetailArticle-Payload
     * und dem auf dem Test-Setup definierten Hauptartikel.
     *
     * @param array<int, mixed> $detailedArticleQuantities
     * @param array<string, mixed> $overrides
     */
    private function buildUpdateRequest(array $detailedArticleQuantities, array $overrides = []): UpdateInventoryArticleRequest
    {
        $payload = array_merge([
            'name' => $this->article->name,
            'description' => $this->article->description ?? '',
            'inventory_category_id' => $this->category->id,
            'quantity' => $this->article->quantity,
            'is_detailed_quantity' => true,
            'properties' => [],
            'detailed_article_quantities' => $detailedArticleQuantities,
            'statusValues' => [],
            'main_image_index' => 0,
        ], $overrides);

        return UpdateInventoryArticleRequest::create('/test', 'PATCH', $payload);
    }

    #[Test]
    public function keeps_existing_detail_article_ids_and_inventory_numbers_when_nothing_changes(): void
    {
        $service = app(InventoryArticleService::class);

        $request = $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
            $this->detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        ]);

        $service->update($this->article, $request);

        $fresh = $this->article->fresh('detailedArticleQuantities');

        $this->assertCount(3, $fresh->detailedArticleQuantities);
        $this->assertEqualsCanonicalizing(
            [$this->detail1->id, $this->detail2->id, $this->detail3->id],
            $fresh->detailedArticleQuantities->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [
                $this->detail1->inventory_number,
                $this->detail2->inventory_number,
                $this->detail3->inventory_number,
            ],
            $fresh->detailedArticleQuantities->pluck('inventory_number')->all()
        );
        $this->assertEqualsCanonicalizing(
            [1, 2, 3],
            $fresh->detailedArticleQuantities->pluck('detail_number')->all()
        );
    }

    #[Test]
    public function updates_fields_of_existing_detail_articles_by_id(): void
    {
        $service = app(InventoryArticleService::class);

        $request = $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A neu', 'geändert', 7),
            $this->detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        ]);

        $service->update($this->article, $request);

        $reloaded = $this->detail1->fresh();

        $this->assertSame('Stuhl A neu', $reloaded->name);
        $this->assertSame('geändert', $reloaded->description);
        $this->assertEquals(7, $reloaded->quantity);
        $this->assertSame(1, $reloaded->detail_number);
        $this->assertSame($this->detail1->inventory_number, $reloaded->inventory_number);
    }

    #[Test]
    public function soft_deletes_detail_articles_missing_from_request_and_keeps_numbers_locked(): void
    {
        $service = app(InventoryArticleService::class);

        // detail2 wird im Request weggelassen → muss soft-deleted werden
        $request = $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        ]);

        $service->update($this->article, $request);

        $this->assertNull(InventoryDetailedQuantityArticle::find($this->detail2->id));

        $trashed = InventoryDetailedQuantityArticle::withTrashed()->find($this->detail2->id);
        $this->assertNotNull($trashed);
        $this->assertSame(2, $trashed->detail_number);
    }

    #[Test]
    public function does_not_reuse_detail_numbers_when_soft_deleted_detail_articles_exist(): void
    {
        $service = app(InventoryArticleService::class);

        // 1) detail2 soft-deleten
        $service->update($this->article, $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        ]));

        // 2) Neues DetailArticle ohne id hinzufügen → muss detail_number 4 bekommen, NICHT 2
        $service->update($this->article, $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
            $this->detailPayload(null, 'Stuhl D', 'neu'),
        ]));

        $newest = $this->article->detailedArticleQuantities()->where('name', 'Stuhl D')->first();

        $this->assertNotNull($newest);
        $this->assertSame(4, $newest->detail_number);
        $this->assertSame($this->article->inventory_number . '-004', $newest->inventory_number);
        $this->assertSame($this->article->external_id . '-4', $newest->external_id);
    }

    #[Test]
    public function synchronizes_detail_article_properties_via_pivot_sync(): void
    {
        $service = app(InventoryArticleService::class);
        $propAttach = [['id' => $this->property->id, 'value' => 'Holz']];

        // 1) Property an detail1 anhängen
        $service->update($this->article, $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster', 1, $propAttach),
            $this->detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        ]));

        $detail1 = $this->detail1->fresh('properties');
        $this->assertCount(1, $detail1->properties);
        $this->assertSame('Holz', $detail1->properties->first()->pivot->value);

        // 2) Wert ändern
        $propUpdate = [['id' => $this->property->id, 'value' => 'Metall']];
        $service->update($this->article, $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster', 1, $propUpdate),
            $this->detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        ]));

        $detail1 = $this->detail1->fresh('properties');
        $this->assertSame('Metall', $detail1->properties->first()->pivot->value);

        // 3) Property entfernen → leeres properties-Array
        $service->update($this->article, $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
            $this->detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
            $this->detailPayload($this->detail3->id, 'Stuhl C', 'dritter'),
        ]));

        $detail1 = $this->detail1->fresh('properties');
        $this->assertCount(0, $detail1->properties);
    }

    #[Test]
    public function creates_new_detail_articles_without_an_id_and_assigns_next_free_number(): void
    {
        $service = app(InventoryArticleService::class);

        // detail3 wird weggelassen + zwei neue (ohne id) hinzugefügt
        $service->update($this->article, $this->buildUpdateRequest([
            $this->detailPayload($this->detail1->id, 'Stuhl A', 'erster'),
            $this->detailPayload($this->detail2->id, 'Stuhl B', 'zweiter'),
            $this->detailPayload(null, 'Stuhl X', 'neu1'),
            $this->detailPayload(null, 'Stuhl Y', 'neu2'),
        ]));

        $mainInventoryNumber = $this->article->inventory_number;
        $mainExternalId = $this->article->external_id;

        $stuhlX = $this->article->detailedArticleQuantities()->where('name', 'Stuhl X')->first();
        $stuhlY = $this->article->detailedArticleQuantities()->where('name', 'Stuhl Y')->first();

        $this->assertSame(4, $stuhlX->detail_number);
        $this->assertSame($mainInventoryNumber . '-004', $stuhlX->inventory_number);
        $this->assertSame($mainExternalId . '-4', $stuhlX->external_id);
        $this->assertSame(5, $stuhlY->detail_number);
        $this->assertSame($mainInventoryNumber . '-005', $stuhlY->inventory_number);
        $this->assertSame($mainExternalId . '-5', $stuhlY->external_id);
    }

    #[Test]
    public function rejects_detail_article_ids_belonging_to_a_different_parent_article(): void
    {
        // anderer Artikel mit eigenem DetailArticle
        $otherArticle = InventoryArticle::factory()->create([
            'name' => 'Tisch',
            'inventory_category_id' => $this->category->id,
            'is_detailed_quantity' => true,
            'quantity' => 1,
        ]);
        $otherDetail = $otherArticle->detailedArticleQuantities()->create([
            'name' => 'Tisch A',
            'quantity' => 1,
            'inventory_article_status_id' => $this->status->id,
            'detail_number' => 1,
            'external_id' => TypeNumberGenerator::generateDetailExternalId($otherArticle->external_id, 1),
            'inventory_number' => TypeNumberGenerator::generateDetailInventoryNumber($otherArticle->inventory_number, 1),
        ]);

        // Versuch, otherDetail-ID an unseren Artikel zu hängen
        $payload = [
            'name' => $this->article->name,
            'description' => '',
            'inventory_category_id' => $this->category->id,
            'quantity' => 3,
            'is_detailed_quantity' => true,
            'main_image_index' => 0,
            'detailed_article_quantities' => [[
                'id' => $otherDetail->id, // gehört zu einem ANDEREN Artikel
                'name' => 'Hijack',
                'description' => '',
                'quantity' => 1,
                'properties' => [],
                'status' => ['id' => $this->status->id],
            ]],
        ];

        $response = $this->patch(
            route('inventory-management.articles.update', $this->article->id),
            $payload
        );

        $response->assertSessionHasErrors('detailed_article_quantities.0.id');
        $this->assertSame('Tisch A', $otherDetail->fresh()->name); // unverändert
    }
}
