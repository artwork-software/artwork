<?php

namespace Tests\Unit\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Services\InventoryArticleService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InventoryArticleServiceTest extends TestCase
{
    private InventoryArticleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryArticleService::class);
    }

    #[Test]
    public function count_returns_total_articles(): void
    {
        InventoryArticle::factory()->count(4)->create();

        $this->assertSame(4, $this->service->count());
    }

    #[Test]
    public function delete_soft_deletes_article(): void
    {
        $article = InventoryArticle::factory()->create();

        $this->service->delete($article);

        $this->assertSoftDeleted('inventory_articles', ['id' => $article->id]);
    }

    #[Test]
    public function get_all_trashed_returns_soft_deleted_articles(): void
    {
        $alive = InventoryArticle::factory()->create();
        $deleted = InventoryArticle::factory()->create();
        $deleted->delete();

        $trashed = $this->service->getAllTrashed();

        $this->assertInstanceOf(Collection::class, $trashed);
        $this->assertCount(1, $trashed);
        $this->assertSame($deleted->id, $trashed->first()->id);
    }

    #[Test]
    public function force_delete_hard_deletes_article(): void
    {
        $article = InventoryArticle::factory()->create();
        $article->delete();

        $this->service->forceDelete($article);

        $this->assertDatabaseMissing('inventory_articles', ['id' => $article->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_article(): void
    {
        $article = InventoryArticle::factory()->create();
        $article->delete();

        $this->service->restore($article);

        $this->assertDatabaseHas('inventory_articles', ['id' => $article->id, 'deleted_at' => null]);
    }

    #[Test]
    public function get_counts_by_status_returns_array_for_empty_collection(): void
    {
        $result = $this->service->getCountsByStatus(collect());

        $this->assertSame([], $result);
    }
}
