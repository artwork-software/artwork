<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Http\Controllers\ProductBasketArticleController;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\ProductBasketArticle;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Eine Warenkorb-Position mit Menge 0 blieb stehen; das Speichern der Ausgabe aus dem
 * Warenkorb scheiterte danach an der Mindestmenge 1.
 */
final class ProductBasketQuantityTest extends FeatureTestCase
{
    private function basketArticle(int $quantity): ProductBasketArticle
    {
        $user = $this->actingAsAdmin();
        $basket = $user->productBasket()->create(['name' => 'Standard']);

        return ProductBasketArticle::query()->create([
            'product_basket_id' => $basket->id,
            'article_id' => InventoryArticle::factory()->create()->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function decreasing_to_zero_removes_the_position(): void
    {
        $basketArticle = $this->basketArticle(1);

        $this->postJson(route('inventory.product_basket.update_quantity', $basketArticle), ['delta' => -1])
            ->assertOk()
            ->assertJsonPath('quantity', 0);

        $this->assertModelMissing($basketArticle);
    }

    #[Test]
    public function setting_zero_directly_removes_the_position(): void
    {
        $basketArticle = $this->basketArticle(3);

        $this->postJson(route('inventory.product_basket.update_quantity.single', $basketArticle), ['quantity' => 0])
            ->assertOk();

        $this->assertModelMissing($basketArticle);
    }

    #[Test]
    public function decreasing_a_position_removed_by_a_parallel_request_answers_as_removed(): void
    {
        $basketArticle = $this->basketArticle(1);
        // Doppelklick: beide Requests haben die Position gebunden, der erste hat sie schon entfernt
        ProductBasketArticle::query()->whereKey($basketArticle->id)->delete();

        $response = app(ProductBasketArticleController::class)->updateQuantity(
            $basketArticle,
            Request::create('/', 'POST', ['delta' => -1])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['basket_article_id' => $basketArticle->id, 'quantity' => 0],
            $response->getData(true)
        );
        $this->assertModelMissing($basketArticle);
    }

    #[Test]
    public function positive_quantities_are_stored(): void
    {
        $basketArticle = $this->basketArticle(1);

        $this->postJson(route('inventory.product_basket.update_quantity', $basketArticle), ['target' => 4])
            ->assertOk()
            ->assertJsonPath('quantity', 4);

        $this->assertSame(4, (int) $basketArticle->fresh()->quantity);
    }
}
