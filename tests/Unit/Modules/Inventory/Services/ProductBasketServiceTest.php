<?php

namespace Tests\Unit\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\ProductBasket;
use Artwork\Modules\Inventory\Services\ProductBasketService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProductBasketServiceTest extends TestCase
{
    private ProductBasketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProductBasketService::class);
    }

    #[Test]
    public function create_basis_basket_creates_basket_for_user(): void
    {
        $user = User::factory()->create();

        $this->service->createBasisBasket($user);

        $this->assertDatabaseHas('product_baskets', ['user_id' => $user->id]);
    }

    #[Test]
    public function get_user_basket_creates_basket_when_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $basket = $this->service->getUserBasket();

        $this->assertSame($user->id, $basket->user_id);
        $this->assertDatabaseHas('product_baskets', ['user_id' => $user->id]);
    }

    #[Test]
    public function get_user_basket_returns_existing_basket(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->service->createBasisBasket($user);
        $existingId = ProductBasket::where('user_id', $user->id)->first()->id;

        $basket = $this->service->getUserBasket();

        $this->assertSame($existingId, $basket->id);
    }

    #[Test]
    public function add_article_to_basket_creates_basket_article(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->service->createBasisBasket($user);
        $article = InventoryArticle::factory()->create();

        $this->service->addArticleToBasket($article->id, 5);

        $this->assertDatabaseHas('product_basket_articles', [
            'article_id' => $article->id,
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function add_article_to_basket_increments_existing_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->service->createBasisBasket($user);
        $article = InventoryArticle::factory()->create();
        $this->service->addArticleToBasket($article->id, 3);

        $this->service->addArticleToBasket($article->id, 4);

        $this->assertDatabaseHas('product_basket_articles', [
            'article_id' => $article->id,
            'quantity' => 7,
        ]);
    }
}
