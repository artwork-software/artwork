<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Exports\InventoryArticleListExport;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Artikelübersicht des Inventars: Sortierung, Excel-Export und Sammelaktionen.
 */
final class InventoryArticleListFeaturesTest extends TestCase
{
    private InventoryCategory $category;

    private InventorySubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = InventoryCategory::factory()->create(['name' => 'Zz Sortierprobe']);
        $this->subCategory = InventorySubCategory::factory()->create([
            'inventory_category_id' => $this->category->id,
        ]);
    }

    private function article(string $name, int $quantity = 1, array $attributes = []): InventoryArticle
    {
        return InventoryArticle::factory()->create(array_merge([
            'name' => $name,
            'quantity' => $quantity,
            'inventory_category_id' => $this->category->id,
            'inventory_sub_category_id' => $this->subCategory->id,
        ], $attributes));
    }

    /**
     * @return array<int, string>
     */
    private function listedNames(array $query = []): array
    {
        $articles = $this->get(route('inventory.category.show', array_merge(
            ['inventoryCategory' => $this->category->id],
            $query
        )))->assertOk()->viewData('page')['props']['articles'];

        return array_column($articles['data'], 'name');
    }

    #[Test]
    public function the_list_sorts_by_allowed_columns_and_ignores_unknown_ones(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_VIEW->value);
        $this->article('Bravo', 5);
        $this->article('Alpha', 1);
        $this->article('Charlie', 9);

        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $this->listedNames());
        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], $this->listedNames(['sort' => 'name', 'direction' => 'desc']));
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $this->listedNames(['sort' => 'quantity']));
        $this->assertSame(
            ['Charlie', 'Bravo', 'Alpha'],
            $this->listedNames(['sort' => 'quantity', 'direction' => 'desc'])
        );
        // unbekannte Spalte: Standardreihenfolge statt SQL-Fehler oder freier Spaltenwahl
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $this->listedNames(['sort' => 'description, id desc']));
    }

    #[Test]
    public function the_export_uses_the_list_filters_and_the_selection(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_VIEW->value);
        $first = $this->article('Exportprobe Eins', 4, ['inventory_number' => '00042']);
        $second = $this->article('Exportprobe Zwei', 2);
        $tag = InventoryTag::factory()->create(['color' => '#000000', 'name' => 'Leihware']);
        $first->tags()->attach($tag->id);

        $response = $this->get(route('inventory.articles.export', [
            'category_id' => $this->category->id,
            'ids' => $first->id,
        ]))->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->get(route('inventory.articles.export', ['search' => '*Exportprobe*', 'filters' => '[]']))->assertOk();

        $statuses = InventoryArticleStatus::query()->orderBy('order')->get();
        $export = new InventoryArticleListExport(
            InventoryArticle::query()
                ->whereKey([$first->id, $second->id])
                ->with(['category', 'subCategory', 'properties', 'statusValues', 'detailedArticleQuantities', 'tags'])
                ->orderBy('name')
                ->get(),
            $statuses,
            'INV-'
        );
        $rows = $export->array();
        $headings = $export->headings();

        $this->assertCount(2, $rows);
        $this->assertCount(count($headings), $rows[0]);
        $this->assertSame('Exportprobe Eins', $rows[0][0]);
        $this->assertSame('INV-00042', $rows[0][1]);
        $this->assertSame('Zz Sortierprobe', $rows[0][3]);
        $this->assertSame(4, (int) $rows[0][5]);
        $this->assertSame('Leihware', $rows[0][6 + $statuses->count()]);
        $this->assertStringStartsWith('INV-', (string) $rows[1][1]);
    }

    #[Test]
    public function the_export_requires_the_view_permission(): void
    {
        $this->actingAsUserWith([]);
        $this->get(route('inventory.articles.export'))->assertForbidden();
    }

    #[Test]
    public function bulk_delete_moves_the_selection_to_the_trash_or_nothing_at_all(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_DELETE->value);
        $first = $this->article('Erster');
        $second = $this->article('Zweiter');
        $kept = $this->article('Bleibt');

        $route = route('inventory-management.articles.bulk-delete');
        $this->postJson($route, ['ids' => [$first->id, 999999]])->assertNotFound();
        $this->postJson($route, ['ids' => []])->assertUnprocessable();
        $this->assertNotNull(InventoryArticle::find($first->id));

        $this->postJson($route, ['ids' => [$first->id, $second->id]])
            ->assertOk()
            ->assertExactJson(['deleted' => 2]);

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
        $this->assertNotNull(InventoryArticle::find($kept->id));
    }

    #[Test]
    public function bulk_delete_stops_at_articles_protected_by_restricted_tags(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_DELETE->value);
        $free = $this->article('Frei');
        $protected = $this->article('Geschützt');
        $tag = InventoryTag::factory()->create(['color' => '#000000', 'has_restricted_permissions' => true]);
        $protected->tags()->attach($tag->id);

        $this->postJson(route('inventory-management.articles.bulk-delete'), ['ids' => [$free->id, $protected->id]])
            ->assertForbidden();

        $this->assertNotNull(InventoryArticle::find($free->id));
        $this->assertNotNull(InventoryArticle::find($protected->id));
    }

    #[Test]
    public function bulk_update_moves_articles_to_another_category(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $first = $this->article('Erster');
        $second = $this->article('Zweiter');
        $target = InventoryCategory::factory()->create();
        $targetSub = InventorySubCategory::factory()->create(['inventory_category_id' => $target->id]);
        $route = route('inventory-management.articles.bulk-update');

        // Unterkategorie einer anderen Kategorie: nichts ändert sich
        $this->postJson($route, [
            'ids' => [$first->id, $second->id],
            'action' => 'category',
            'inventory_category_id' => $target->id,
            'inventory_sub_category_id' => $this->subCategory->id,
        ])->assertUnprocessable();
        $this->assertSame($this->category->id, $first->fresh()->inventory_category_id);

        $this->postJson($route, [
            'ids' => [$first->id, $second->id],
            'action' => 'category',
            'inventory_category_id' => $target->id,
            'inventory_sub_category_id' => $targetSub->id,
        ])->assertOk()->assertExactJson(['updated' => 2]);
        $this->assertSame($target->id, $second->fresh()->inventory_category_id);
        $this->assertSame($targetSub->id, $second->fresh()->inventory_sub_category_id);

        // ohne Unterkategorie: die alte wird geleert
        $this->postJson($route, [
            'ids' => [$first->id],
            'action' => 'category',
            'inventory_category_id' => $this->category->id,
        ])->assertOk();
        $this->assertSame($this->category->id, $first->fresh()->inventory_category_id);
        $this->assertNull($first->fresh()->inventory_sub_category_id);
    }

    #[Test]
    public function bulk_update_adds_and_removes_tags_without_touching_other_tags(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $first = $this->article('Erster');
        $second = $this->article('Zweiter');
        $existing = InventoryTag::factory()->create(['color' => '#111111']);
        $added = InventoryTag::factory()->create(['color' => '#222222']);
        $first->tags()->attach($existing->id);
        $route = route('inventory-management.articles.bulk-update');

        $this->postJson($route, [
            'ids' => [$first->id, $second->id],
            'action' => 'add_tags',
            'tag_ids' => [$added->id],
        ])->assertOk()->assertExactJson(['updated' => 2]);
        $this->assertEqualsCanonicalizing(
            [$existing->id, $added->id],
            $first->tags()->pluck('inventory_tags.id')->all()
        );
        $this->assertSame([$added->id], $second->tags()->pluck('inventory_tags.id')->all());

        $this->postJson($route, [
            'ids' => [$first->id, $second->id],
            'action' => 'remove_tags',
            'tag_ids' => [$added->id],
        ])->assertOk();
        $this->assertSame([$existing->id], $first->tags()->pluck('inventory_tags.id')->all());
        $this->assertSame([], $second->tags()->pluck('inventory_tags.id')->all());

        $this->postJson($route, ['ids' => [$first->id], 'action' => 'add_tags'])->assertUnprocessable();
    }

    #[Test]
    public function bulk_update_refuses_restricted_tags_the_user_may_not_use(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $article = $this->article('Erster');
        $restricted = InventoryTag::factory()->create(['color' => '#000000', 'has_restricted_permissions' => true]);

        $this->postJson(route('inventory-management.articles.bulk-update'), [
            'ids' => [$article->id],
            'action' => 'add_tags',
            'tag_ids' => [$restricted->id],
        ])->assertForbidden();

        $this->assertSame(0, $article->tags()->count());
    }

    #[Test]
    public function bulk_actions_require_their_permissions(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_VIEW->value);
        $article = $this->article('Geschützt');

        $this->postJson(route('inventory-management.articles.bulk-delete'), ['ids' => [$article->id]])
            ->assertForbidden();
        $this->postJson(route('inventory-management.articles.bulk-update'), [
            'ids' => [$article->id],
            'action' => 'category',
            'inventory_category_id' => $this->category->id,
        ])->assertForbidden();

        $this->assertNotNull(InventoryArticle::find($article->id));
    }
}
