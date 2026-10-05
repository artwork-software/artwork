<?php

namespace Tests\Feature\Modules\Inventory\Characterization;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryUserFilter;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der JSON-Abfragen rund um Inventar-Artikel (Verfügbarkeit einzeln und im
 * Stapel, Nutzungsdaten, Zellen-Details der Disposition, gefilterte Artikelliste, Kategorien mit
 * Nutzerfilter, Ausgaben-Protokoll), der Dispositionsseite und des Inline-Speicherns von
 * Eigenschaften an Einzelartikeln.
 */
final class InventoryArticleQueryEndpointsTest extends FeatureTestCase
{
    private function articleWithReservation(int $quantity, int $reserved, string $day = '2026-11-10'): InventoryArticle
    {
        $article = InventoryArticle::factory()->create(['quantity' => $quantity, 'is_detailed_quantity' => false]);
        $issue = InternalIssue::factory()->create([
            'start_date' => $day,
            'start_time' => '10:00',
            'end_date' => $day,
            'end_time' => '12:00',
        ]);
        $issue->articles()->attach($article->id, ['quantity' => $reserved]);

        return $article;
    }

    #[Test]
    public function the_available_stock_of_one_article_is_returned(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->articleWithReservation(5, 3);

        $this->getJson(route('inventory.articles.available-stock', [
            'inventoryArticle' => $article->id,
            'startDate' => '2026-11-10',
            'endDate' => '2026-11-10',
        ]))
            ->assertOk()
            ->assertJsonPath('availableStock.reserved', 3)
            ->assertJsonPath('availableStock.available', 2);
    }

    #[Test]
    public function the_available_stock_batch_is_keyed_by_article(): void
    {
        $this->actingAs(User::factory()->create());
        $reservedArticle = $this->articleWithReservation(5, 3);
        $freeArticle = InventoryArticle::factory()->create(['quantity' => 4, 'is_detailed_quantity' => false]);

        $this->postJson(route('inventory.articles.available-stock.batch'), [
            'article_ids' => [$reservedArticle->id, $freeArticle->id],
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
        ])
            ->assertOk()
            ->assertJsonPath("data.{$reservedArticle->id}.available", 2)
            ->assertJsonPath("data.{$freeArticle->id}.available", 4);
    }

    #[Test]
    public function the_batch_ignores_bookings_outside_the_requested_hours(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->articleWithReservation(5, 3);

        $this->postJson(route('inventory.articles.available-stock.batch'), [
            'article_ids' => [$article->id],
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'start_time' => '14:00',
            'end_time' => '16:00',
        ])
            ->assertOk()
            ->assertJsonPath("data.{$article->id}.available", 5);
    }

    #[Test]
    public function the_batch_validates_its_range(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('inventory.articles.available-stock.batch'), [
            'article_ids' => [],
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-09',
        ])->assertUnprocessable()->assertJsonValidationErrors(['article_ids', 'end_date']);
    }

    #[Test]
    public function usage_data_covers_the_requested_range(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->articleWithReservation(5, 3);

        $this->getJson(route('inventory.articles.usage', [
            'article_id' => $article->id,
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-11',
        ]))
            ->assertOk()
            ->assertJsonPath('data.article.id', $article->id)
            ->assertJsonPath('data.start_date', '2026-11-09');
    }

    #[Test]
    public function usage_data_rejects_ranges_longer_than_a_year(): void
    {
        $this->actingAs(User::factory()->create());
        $article = InventoryArticle::factory()->create();

        $this->getJson(route('inventory.articles.usage', [
            'article_id' => $article->id,
            'start_date' => '2026-01-01',
            'end_date' => '2027-06-01',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('error', 'Date range must not exceed one year.');
    }

    #[Test]
    public function planning_cell_details_list_the_issues_of_the_day(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->articleWithReservation(5, 3);

        $this->getJson(route('inventory.articles.planning-cell', [
            'article_id' => $article->id,
            'date' => '2026-11-10',
        ]))
            ->assertOk()
            ->assertJsonPath('data.article.id', $article->id)
            ->assertJsonPath('data.date', '2026-11-10')
            ->assertJsonCount(1, 'data.internal')
            ->assertJsonPath('data.peak_usage', 3);
    }

    #[Test]
    public function planning_cell_details_need_an_existing_article(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('inventory.articles.planning-cell', ['article_id' => 0, 'date' => '2026-11-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('article_id');
    }

    #[Test]
    public function the_article_list_follows_the_saved_user_filter_and_search(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = InventoryCategory::factory()->create();
        $match = InventoryArticle::factory()->create([
            'name' => 'Zz-Filter-Treffer',
            'inventory_category_id' => $category->id,
        ]);
        InventoryArticle::factory()->create(['name' => 'Zz-Filter-Andere-Kategorie']);
        InventoryArticle::factory()->create([
            'name' => 'Zz-Filter-Nicht-gesucht',
            'inventory_category_id' => $category->id,
        ]);
        InventoryUserFilter::query()->create(['user_id' => $user->id, 'category_ids' => [$category->id]]);

        $this->getJson(route('inventory.articles.api', ['search' => 'Zz-Filter-Treffer']))
            ->assertOk()
            ->assertJsonPath('articles.total', 1)
            ->assertJsonPath('articles.data.0.id', $match->id);
    }

    #[Test]
    public function all_categories_only_carry_articles_matching_the_user_filter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = InventoryCategory::factory()->create();
        $match = InventoryArticle::factory()->create(['inventory_category_id' => $category->id]);
        $tag = InventoryTag::factory()->create(['color' => '#000000']);
        $match->tags()->attach($tag->id);
        InventoryArticle::factory()->create(['inventory_category_id' => $category->id]);
        InventoryUserFilter::query()->create(['user_id' => $user->id, 'tag_ids' => [$tag->id]]);

        $response = $this->getJson(route('inventory.categories.get-all'))->assertOk();

        $returned = collect($response->json('categories'))->firstWhere('id', $category->id);
        $this->assertSame([$match->id], array_column($returned['articles'], 'id'));
    }

    #[Test]
    public function all_categories_with_a_filter_without_matches_return_no_articles(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = InventoryCategory::factory()->create();
        InventoryArticle::factory()->create(['inventory_category_id' => $category->id]);
        $tag = InventoryTag::factory()->create(['color' => '#000000']);
        InventoryUserFilter::query()->create(['user_id' => $user->id, 'tag_ids' => [$tag->id]]);

        $response = $this->getJson(route('inventory.categories.get-all'))->assertOk();

        $returned = collect($response->json('categories'))->firstWhere('id', $category->id);
        $this->assertSame([], $returned['articles']);
    }

    #[Test]
    public function a_detailed_article_property_is_saved_inline(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $detail = InventoryDetailedQuantityArticle::factory()->create();
        $property = InventoryArticleProperties::factory()->create(['type' => 'string']);

        $this->patchJson(route('inventory-management.articles.detailed.update-property', $detail), [
            'property_id' => $property->id,
            'value' => 'Seriennr. 42',
        ])->assertOk()->assertJson(['success' => true]);

        $this->patchJson(route('inventory-management.articles.detailed.update-property', $detail), [
            'property_id' => $property->id,
            'value' => null,
        ])->assertOk();

        $this->assertDatabaseHas('inventory_property_values', [
            'inventory_propertyable_type' => InventoryDetailedQuantityArticle::class,
            'inventory_propertyable_id' => $detail->id,
            'inventory_article_property_id' => $property->id,
            'value' => '',
        ]);
        $this->assertSame(1, $detail->properties()->count());
    }

    #[Test]
    public function detailed_article_properties_need_the_edit_permission_and_tag_access(): void
    {
        $this->actingAs(User::factory()->create());
        $detail = InventoryDetailedQuantityArticle::factory()->create();
        $property = InventoryArticleProperties::factory()->create(['type' => 'string']);
        $payload = ['property_id' => $property->id, 'value' => 'X'];

        $this->patchJson(route('inventory-management.articles.detailed.update-property', $detail), $payload)
            ->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::INVENTORY_CREATE_EDIT->value);
        $tag = InventoryTag::factory()->create(['color' => '#000000', 'has_restricted_permissions' => true]);
        InventoryArticle::query()->findOrFail($detail->inventory_article_id)->tags()->attach($tag->id);

        $this->patchJson(route('inventory-management.articles.detailed.update-property', $detail), $payload)
            ->assertForbidden();

        $this->assertSame(0, $detail->properties()->count());
    }

    #[Test]
    public function the_disposition_page_needs_the_disposition_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('inventory-management.article.planning'))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::INVENTORY_DISPOSITION->value);
        $this->get(route('inventory-management.article.planning'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inventory/InventoryArticlePlanning')
                ->has('projectMaterialIssueTabId'));
    }

    #[Test]
    public function the_material_issue_log_needs_its_permission_and_defaults_to_the_current_month(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson(route('material-issue-log.index'))->assertForbidden();

        $this->actingAsUserWith(PermissionEnum::MATERIAL_ISSUE_LOG_VIEW->value);
        $this->getJson(route('material-issue-log.index'))
            ->assertOk()
            ->assertJsonPath('range.start_date', now()->startOfMonth()->toDateString())
            ->assertJsonPath('range.end_date', now()->endOfMonth()->toDateString())
            ->assertJsonStructure(['logs' => ['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]]);
    }

    #[Test]
    public function the_material_issue_log_lists_activity_of_issues_in_range(): void
    {
        $this->actingAsUserWith(PermissionEnum::MATERIAL_ISSUE_LOG_VIEW->value);
        $issue = InternalIssue::factory()->create([
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-11',
        ]);
        activity('material_issue')->performedOn($issue)->log('created');
        $outside = InternalIssue::factory()->create([
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-11',
        ]);
        activity('material_issue')->performedOn($outside)->log('created');

        $this->getJson(route('material-issue-log.index', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'per_page' => 500,
        ]))
            ->assertOk()
            ->assertJsonPath('logs.meta.per_page', 200)
            ->assertJsonPath('logs.meta.total', 1)
            ->assertJsonPath('logs.data.0.subject_id', $issue->id);
    }
}
