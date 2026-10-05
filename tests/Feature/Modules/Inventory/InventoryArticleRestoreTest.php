<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Artwork\Modules\Inventory\Repositories\InventoryArticleRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class InventoryArticleRestoreTest extends FeatureTestCase
{
    #[Test]
    public function restoring_an_article_keeps_previously_removed_detailed_articles_removed(): void
    {
        $repository = app(InventoryArticleRepository::class);
        $article = InventoryArticle::factory()->create(['is_detailed_quantity' => true, 'quantity' => 2]);
        $kept = InventoryDetailedQuantityArticle::factory()->create(['inventory_article_id' => $article->id]);
        $removedEarlier = InventoryDetailedQuantityArticle::factory()->create(['inventory_article_id' => $article->id]);

        // Beim Bearbeiten entfernt, Tage bevor der Artikel selbst in den Papierkorb ging
        $this->travel(-3)->days();
        $removedEarlier->delete();
        $this->travelBack();

        $repository->delete($article);
        $repository->restore($article->fresh() ?? InventoryArticle::withTrashed()->find($article->id));

        $this->assertNotSoftDeleted($article);
        $this->assertNotSoftDeleted($kept);
        $this->assertSoftDeleted($removedEarlier);
    }
}
