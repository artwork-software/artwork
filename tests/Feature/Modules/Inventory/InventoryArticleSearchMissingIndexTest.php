<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesMeilisearchErrors;
use Tests\Feature\FeatureTestCase;

/**
 * Auf Instanzen ohne inventory_articles-Index (nie ein Artikel indexiert) warf die Artikelsuche
 * eine 500. Sie fällt dann wie die Personensuche auf eine SQL-Suche zurück.
 */
final class InventoryArticleSearchMissingIndexTest extends FeatureTestCase
{
    use FakesMeilisearchErrors;

    #[Test]
    public function article_search_falls_back_to_sql_when_the_index_is_missing(): void
    {
        $this->actingAs(User::factory()->create());
        $this->useMeilisearchEngineFailingWith('index_not_found');
        $byName = InventoryArticle::factory()->create(['name' => 'XLR-Kabel 10m']);
        $byNumber = InventoryArticle::factory()->create(['name' => 'Scheinwerfer', 'inventory_number' => 'XLR-4711']);
        InventoryArticle::factory()->create(['name' => 'Stativ', 'inventory_number' => 'ST-1']);

        $response = $this->postJson(route('inventory.articles.search'), ['article_search' => 'xlr']);

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            [$byName->id, $byNumber->id],
            collect($response->json())->pluck('id')->all()
        );
    }

    #[Test]
    public function article_search_returns_at_most_fifty_articles_in_the_fallback(): void
    {
        $this->actingAs(User::factory()->create());
        $this->useMeilisearchEngineFailingWith('index_not_found');
        InventoryArticle::factory()->count(55)->sequence(
            fn($sequence) => ['name' => 'Kabel ' . $sequence->index]
        )->create();

        $this->postJson(route('inventory.articles.search'), ['article_search' => 'Kabel'])
            ->assertOk()
            ->assertJsonCount(50);
    }
}
