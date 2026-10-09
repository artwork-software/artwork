<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventoryTag;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Mengen und Beziehungen eines Artikels bleiben beim Speichern stimmig: Gesamtmenge = Summe der
 * Statusmengen, keine Doppelzählung nach Umstellung auf Einzelinventar, unvollständige Requests
 * leeren keine Beziehungen, ungültige Statusmengen werden abgelehnt.
 */
final class InventoryArticleConsistencyTest extends FeatureTestCase
{
    private InventoryArticleStatus $ready;
    private InventoryArticleStatus $broken;
    private InventoryCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
        $this->category = InventoryCategory::factory()->create();
        $this->ready = $this->createStatus('Einsatzbereit', true);
        $this->broken = $this->createStatus('Defekt', false);
    }

    private function createStatus(string $name, bool $default): InventoryArticleStatus
    {
        $status = new InventoryArticleStatus(['name' => $name, 'color' => '#000000', 'order' => 1]);
        $status->default = $default;
        $status->save();

        return $status;
    }

    private function articleWithStatusValues(int $ready, int $broken): InventoryArticle
    {
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $this->category->id,
            'inventory_sub_category_id' => null,
            'is_detailed_quantity' => false,
            'quantity' => $ready + $broken,
        ]);
        $article->statusValues()->attach([
            $this->ready->id => ['value' => $ready],
            $this->broken->id => ['value' => $broken],
        ]);

        return $article;
    }

    /**
     * @return array<string, mixed>
     */
    private function basePayload(InventoryArticle $article): array
    {
        return [
            'name' => $article->name,
            'description' => '',
            'inventory_category_id' => $this->category->id,
            'quantity' => $article->quantity,
            'is_detailed_quantity' => (bool) $article->is_detailed_quantity,
            'main_image_index' => 0,
        ];
    }

    private function statusValue(InventoryArticle $article, InventoryArticleStatus $status): ?int
    {
        $value = $article->statusValues()->where('inventory_article_status_id', $status->id)->first()?->pivot->value;

        return $value === null ? null : (int) $value;
    }

    #[Test]
    public function inline_quantity_changes_are_balanced_in_the_default_status(): void
    {
        $article = $this->articleWithStatusValues(7, 3);

        $this->patchJson(route('inventory-management.articles.update-field', $article), [
            'field' => 'quantity',
            'value' => 12,
        ])->assertSuccessful();

        $this->assertSame(12, $article->fresh()->quantity);
        $this->assertSame(9, $this->statusValue($article, $this->ready));
        $this->assertSame(3, $this->statusValue($article, $this->broken));

        // Weniger als die übrigen Status zusammen geht nicht
        $this->patchJson(route('inventory-management.articles.update-field', $article), [
            'field' => 'quantity',
            'value' => 2,
        ])->assertUnprocessable();
        $this->assertSame(12, $article->fresh()->quantity);

        // Nur ganze Stückzahlen
        $this->patchJson(route('inventory-management.articles.update-field', $article), [
            'field' => 'quantity',
            'value' => 2.5,
        ])->assertUnprocessable();
    }

    #[Test]
    public function inline_quantity_changes_return_the_balanced_status_values_and_a_readable_error(): void
    {
        $article = $this->articleWithStatusValues(7, 3);

        // Das Modal zieht den Standard-Status aus der Antwort nach – sonst überschreibt
        // „Speichern“ den Ausgleich wieder mit den alten Statusmengen
        $statusValues = $this->patchJson(route('inventory-management.articles.update-field', $article), [
            'field' => 'quantity',
            'value' => 15,
        ])->assertSuccessful()->json('status_values');

        $this->assertSame(
            collect([$this->ready->id => 12, $this->broken->id => 3])->sortKeys()->all(),
            collect($statusValues)->pluck('value', 'id')->sortKeys()->all()
        );
        // nur der Standard-Status ist markiert – das Modal übernimmt nur ihn
        $this->assertSame(
            [$this->ready->id],
            collect($statusValues)->where('default', true)->pluck('id')->all()
        );

        // Die Ablehnung nennt den Grund (das Modal zeigte sonst „Dieses Feld darf nicht leer sein“)
        $this->patchJson(route('inventory-management.articles.update-field', $article), [
            'field' => 'quantity',
            'value' => 2,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', __('The total quantity cannot be lower than the quantities in the other statuses.'));
    }

    #[Test]
    public function an_update_without_status_values_or_tags_keeps_them(): void
    {
        $article = $this->articleWithStatusValues(4, 1);
        $tag = InventoryTag::query()->create(['name' => 'Bühne', 'has_restricted_permissions' => false]);
        $article->tags()->attach($tag->id);

        $this->patch(route('inventory-management.articles.update', $article), $this->basePayload($article))
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $this->statusValue($article, $this->ready));
        $this->assertSame(1, $this->statusValue($article, $this->broken));
        $this->assertTrue($article->tags()->whereKey($tag->id)->exists());
    }

    #[Test]
    public function the_edit_modal_can_remove_the_last_tag_and_the_last_detailed_article(): void
    {
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $this->category->id,
            'inventory_sub_category_id' => null,
            'is_detailed_quantity' => true,
            'quantity' => 1,
        ]);
        $detail = InventoryDetailedQuantityArticle::factory()->create([
            'inventory_article_id' => $article->id,
            'inventory_article_status_id' => $this->ready->id,
        ]);
        $tag = InventoryTag::query()->create(['name' => 'Bühne', 'has_restricted_permissions' => false]);
        $article->tags()->attach($tag->id);

        // FormData lässt leere Listen weg – das Modal schickt nur die Kennung complete_form
        $this->patch(
            route('inventory-management.articles.update', $article),
            $this->basePayload($article) + ['complete_form' => '1']
        )->assertSessionHasNoErrors();

        $this->assertFalse($article->tags()->whereKey($tag->id)->exists());
        $this->assertSoftDeleted($detail);
    }

    #[Test]
    public function an_update_without_detailed_articles_keeps_them(): void
    {
        $article = InventoryArticle::factory()->create([
            'inventory_category_id' => $this->category->id,
            'inventory_sub_category_id' => null,
            'is_detailed_quantity' => true,
            'quantity' => 1,
        ]);
        $detail = InventoryDetailedQuantityArticle::factory()->create([
            'inventory_article_id' => $article->id,
            'inventory_article_status_id' => $this->ready->id,
        ]);

        $this->patch(route('inventory-management.articles.update', $article), $this->basePayload($article))
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted($detail);
    }

    #[Test]
    public function switching_to_detailed_inventory_drops_the_main_status_values(): void
    {
        $article = $this->articleWithStatusValues(2, 0);

        $this->patch(route('inventory-management.articles.update', $article), array_merge(
            $this->basePayload($article),
            [
                'is_detailed_quantity' => true,
                'quantity' => 2,
                'statusValues' => [['id' => $this->ready->id, 'value' => 2]],
                'detailed_article_quantities' => [
                    ['name' => 'A', 'quantity' => 1, 'status' => ['id' => $this->ready->id], 'properties' => []],
                    ['name' => 'B', 'quantity' => 1, 'status' => ['id' => $this->ready->id], 'properties' => []],
                ],
            ]
        ))->assertSessionHasNoErrors();

        // Vorher zusätzlich 2 am Hauptartikel → Statuszählung 4 statt 2
        $this->assertSame(0, $article->statusValues()->count());
        $this->assertSame(2.0, $article->fresh()->readyQuantity());
    }

    #[Test]
    public function negative_or_fractional_status_values_are_rejected(): void
    {
        $article = $this->articleWithStatusValues(1, 0);

        $this->patch(route('inventory-management.articles.update', $article), array_merge(
            $this->basePayload($article),
            ['statusValues' => [['id' => $this->ready->id, 'value' => -3]]]
        ))->assertSessionHasErrors('statusValues.0.value');

        $this->patch(route('inventory-management.articles.update', $article), array_merge(
            $this->basePayload($article),
            ['statusValues' => [['id' => $this->ready->id, 'value' => '1.5']]]
        ))->assertSessionHasErrors('statusValues.0.value');

        $this->assertSame(1, $this->statusValue($article, $this->ready));
    }
}
