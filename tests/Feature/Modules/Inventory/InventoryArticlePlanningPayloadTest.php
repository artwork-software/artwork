<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Models\InventoryUserFilter;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Artikelplanung (/inventory-management/article/planning): Gruppierung, Einsatzbereit-Basis und
 * Abzüge bleiben gleich, die Artikel im Gruppenbaum tragen aber nur noch die gelesenen Felder
 * (id, name) — vorher volle Modelle samt Einzelartikeln, Eigenschaften und Statuswerten.
 */
final class InventoryArticlePlanningPayloadTest extends FeatureTestCase
{
    private User $user;

    private InventoryCategory $category;

    private InventoryArticle $plainArticle;

    private InventoryArticle $statusArticle;

    private InventoryArticle $detailedArticle;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15 12:00:00');
        $this->user = $this->actingAsUserWith(PermissionEnum::INVENTORY_DISPOSITION->value);

        $ready = $this->createStatus('Einsatzbereit', true);
        $broken = $this->createStatus('Defekt');

        $this->category = InventoryCategory::factory()->create(['name' => 'Planungstest Licht']);
        $subCategory = InventorySubCategory::factory()->create([
            'name' => 'Planungstest Scheinwerfer',
            'inventory_category_id' => $this->category->id,
        ]);

        $this->plainArticle = InventoryArticle::factory()->create([
            'name' => 'Kabeltrommel',
            'quantity' => 5,
            'is_detailed_quantity' => false,
            'inventory_category_id' => $this->category->id,
            'inventory_sub_category_id' => null,
        ]);

        $this->statusArticle = InventoryArticle::factory()->create([
            'name' => 'Stufenlinse',
            'quantity' => 10,
            'is_detailed_quantity' => false,
            'inventory_category_id' => $this->category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);
        $this->statusArticle->statusValues()->attach([$ready->id => ['value' => 7], $broken->id => ['value' => 3]]);

        $this->detailedArticle = InventoryArticle::factory()->create([
            'name' => 'Moving Head',
            'quantity' => 3,
            'is_detailed_quantity' => true,
            'inventory_category_id' => $this->category->id,
            'inventory_sub_category_id' => $subCategory->id,
        ]);
        foreach ([[$ready, 1], [$ready, 1], [$broken, 1]] as [$status, $quantity]) {
            InventoryDetailedQuantityArticle::factory()->create([
                'inventory_article_id' => $this->detailedArticle->id,
                'inventory_article_status_id' => $status->id,
                'quantity' => $quantity,
            ]);
        }
        InventoryDetailedQuantityArticle::factory()->create([
            'inventory_article_id' => $this->detailedArticle->id,
            'inventory_article_status_id' => $ready->id,
            'quantity' => 1,
        ])->delete();

        $issue = InternalIssue::factory()->create([
            'start_date' => '2026-11-10',
            'start_time' => '00:00',
            'end_date' => '2026-11-11',
            'end_time' => '23:59',
        ]);
        $issue->articles()->attach($this->statusArticle->id, ['quantity' => 2]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createStatus(string $name, bool $default = false): InventoryArticleStatus
    {
        $status = new InventoryArticleStatus(['name' => $name, 'color' => '#000000', 'order' => 1]);
        $status->default = $default;
        $status->save();

        return $status;
    }

    /**
     * @return array<string, mixed>
     */
    private function planningProps(): array
    {
        return $this->get(route('inventory-management.article.planning'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Inventory/InventoryArticlePlanning'))
            ->viewData('page')['props'];
    }

    /**
     * @param array<string, mixed> $props
     * @return array<string, mixed>|null
     */
    private function groupFor(array $props, string $category): ?array
    {
        return collect($props['groupedArticles'])->firstWhere('category', $category);
    }

    #[Test]
    public function articles_are_grouped_by_category_and_sub_category(): void
    {
        $group = $this->groupFor($this->planningProps(), 'Planungstest Licht');

        $this->assertNotNull($group);
        $this->assertSame([$this->plainArticle->id], array_column($group['articles'], 'id'));
        $this->assertSame(['Kabeltrommel'], array_column($group['articles'], 'name'));
        $this->assertCount(1, $group['subcategories']);
        $this->assertSame('Planungstest Scheinwerfer', $group['subcategories'][0]['name']);
        $this->assertSame(
            [$this->statusArticle->id, $this->detailedArticle->id],
            array_column($group['subcategories'][0]['articles'], 'id')
        );
        $this->assertSame(
            ['Stufenlinse', 'Moving Head'],
            array_column($group['subcategories'][0]['articles'], 'name')
        );
    }

    #[Test]
    public function the_availability_uses_the_ready_quantity_and_subtracts_issues(): void
    {
        $props = $this->planningProps();
        $availability = $props['availability'];

        $this->assertEquals(5, $availability['base'][$this->plainArticle->id]);
        $this->assertEquals(7, $availability['base'][$this->statusArticle->id]);
        $this->assertEquals(2, $availability['base'][$this->detailedArticle->id]);

        $this->assertEquals(5, $availability['deltas']['2026-11-10'][$this->statusArticle->id]);
        $this->assertEquals(5, $availability['deltas']['2026-11-11'][$this->statusArticle->id]);
        $this->assertArrayNotHasKey($this->statusArticle->id, $availability['deltas']['2026-11-12'] ?? []);

        $this->assertSame(['2026-11-01', '2026-11-30'], $props['dataArray']);
        $issue = collect($props['issues'])->first(
            fn (array $issue): bool => in_array($this->statusArticle->id, $issue['article_ids'], true)
        );
        $this->assertSame([$this->statusArticle->id => 2], $issue['article_quantities']);
    }

    #[Test]
    public function grouped_articles_only_carry_the_fields_the_page_reads(): void
    {
        $group = $this->groupFor($this->planningProps(), 'Planungstest Licht');

        $articles = array_merge($group['articles'], ...array_column($group['subcategories'], 'articles'));
        $this->assertCount(3, $articles);
        foreach ($articles as $article) {
            $article = is_array($article) ? $article : $article->toArray();
            $this->assertSame(['id', 'name'], array_keys($article));
        }
    }

    #[Test]
    public function the_user_filter_still_narrows_the_articles(): void
    {
        $property = InventoryArticleProperties::factory()->create(['type' => 'string', 'is_filterable' => true]);
        $this->statusArticle->properties()->attach($property->id, ['value' => 'Bühne']);
        $this->detailedArticle->properties()->attach($property->id, ['value' => 'Lager']);
        InventoryUserFilter::query()->create([
            'user_id' => $this->user->id,
            'category_ids' => [$this->category->id],
            'property_filters' => [$property->id => 'Bühne'],
        ]);

        $props = $this->planningProps();
        $group = $this->groupFor($props, 'Planungstest Licht');

        $this->assertSame([], $group['articles']);
        $this->assertSame([$this->statusArticle->id], array_column($group['subcategories'][0]['articles'], 'id'));
        $this->assertSame([$this->statusArticle->id], array_map('intval', array_keys($props['availability']['base'])));
    }
}
