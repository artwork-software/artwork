<?php

namespace Tests\Feature\Modules\Inventory\Characterization;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryUserFilter;
use Artwork\Modules\Inventory\Models\ProductBasket;
use Artwork\Modules\Inventory\Models\ProductBasketArticle;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der nutzerbezogenen Inventar-Endpunkte: eigener Warenkorb (hinzufügen, laden,
 * Position und ganzen Inhalt entfernen, fremde Körbe gesperrt) sowie persönliche Ansichts- und
 * Filtereinstellungen. Keiner dieser Endpunkte verlangt eine Inventar-Berechtigung.
 */
final class ProductBasketAndPreferencesTest extends FeatureTestCase
{
    private function basketArticleOf(User $owner, int $quantity = 2): ProductBasketArticle
    {
        $basket = ProductBasket::factory()->create(['user_id' => $owner->id]);

        return ProductBasketArticle::factory()->create([
            'product_basket_id' => $basket->id,
            'quantity' => $quantity,
        ]);
    }

    #[Test]
    public function adding_an_article_creates_the_basket_and_position(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $article = InventoryArticle::factory()->create();

        $this->post(route('inventory.basket.add'), ['article_id' => $article->id, 'quantity' => 2])->assertOk();

        $basket = $user->productBasket()->sole();
        $this->assertDatabaseHas('product_basket_articles', [
            'product_basket_id' => $basket->id,
            'article_id' => $article->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function adding_the_same_article_again_increases_the_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $article = InventoryArticle::factory()->create();

        $this->post(route('inventory.basket.add'), ['article_id' => $article->id, 'quantity' => 2])->assertOk();
        $this->post(route('inventory.basket.add'), ['article_id' => $article->id, 'quantity' => 3])->assertOk();

        $this->assertSame(
            5,
            (int) $user->productBasket()->sole()->basketArticles()->where('article_id', $article->id)->sole()->quantity
        );
    }

    #[Test]
    public function adding_needs_an_existing_article_and_a_positive_quantity(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('inventory.basket.add'), ['article_id' => 0, 'quantity' => 0])
            ->assertSessionHasErrors(['article_id', 'quantity']);
    }

    #[Test]
    public function the_own_baskets_are_returned_with_articles(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $basketArticle = $this->basketArticleOf($user);
        $this->basketArticleOf(User::factory()->create());

        $this->getJson(route('inventory.product_basket.get_baskets'))
            ->assertOk()
            ->assertJsonCount(1, 'baskets')
            ->assertJsonPath('baskets.0.basket_articles.0.id', $basketArticle->id)
            ->assertJsonPath('baskets.0.basket_articles.0.article.id', $basketArticle->article_id);
    }

    #[Test]
    public function an_own_basket_position_is_removed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $basketArticle = $this->basketArticleOf($user);

        $this->deleteJson(route('inventory.product_basket.remove', $basketArticle))
            ->assertOk()
            ->assertJson(['deleted' => true, 'basket_article_id' => $basketArticle->id]);

        $this->assertModelMissing($basketArticle);
    }

    #[Test]
    public function a_foreign_basket_position_cannot_be_removed(): void
    {
        $this->actingAs(User::factory()->create());
        $foreign = $this->basketArticleOf(User::factory()->create());

        $this->deleteJson(route('inventory.product_basket.remove', $foreign))->assertForbidden();

        $this->assertModelExists($foreign);
    }

    #[Test]
    public function emptying_the_own_basket_removes_all_positions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $first = $this->basketArticleOf($user);
        $second = ProductBasketArticle::factory()->create(['product_basket_id' => $first->product_basket_id]);

        $this->post(route('inventory.product_basket.remove_articles', $first->product_basket_id))->assertOk();

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        $this->assertDatabaseHas('product_baskets', ['id' => $first->product_basket_id]);
    }

    #[Test]
    public function a_foreign_basket_cannot_be_emptied_or_changed(): void
    {
        $this->actingAs(User::factory()->create());
        $foreign = $this->basketArticleOf(User::factory()->create(), 3);

        $this->post(route('inventory.product_basket.remove_articles', $foreign->product_basket_id))
            ->assertForbidden();
        $this->postJson(route('inventory.product_basket.update_quantity', $foreign), ['target' => 1])
            ->assertForbidden();
        $this->postJson(route('inventory.product_basket.update_quantity.single', $foreign), ['quantity' => 1])
            ->assertForbidden();

        $this->assertSame(3, (int) $foreign->fresh()->quantity);
    }

    #[Test]
    public function the_grid_layout_preference_is_stored_on_the_user(): void
    {
        $user = User::factory()->create(['inventory_grid_layout' => true]);
        $this->actingAs($user);

        $this->post(route('inventory.update-grid-layout'), ['inventory_grid_layout' => false])->assertOk();

        $this->assertFalse($user->fresh()->inventory_grid_layout);
    }

    #[Test]
    public function the_hide_images_preference_is_stored_on_the_user(): void
    {
        $user = User::factory()->create(['inventory_hide_images' => false]);
        $this->actingAs($user);

        $this->post(route('inventory.update-hide-images'), ['inventory_hide_images' => true])->assertOk();

        $this->assertTrue($user->fresh()->inventory_hide_images);
    }

    #[Test]
    public function view_preferences_require_a_boolean(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('inventory.update-grid-layout'), [])->assertSessionHasErrors('inventory_grid_layout');
        $this->post(route('inventory.update-hide-images'), ['inventory_hide_images' => 'vielleicht'])
            ->assertSessionHasErrors('inventory_hide_images');
    }

    #[Test]
    public function the_inventory_filter_is_saved_once_per_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('inventory.filter.store'), ['category_ids' => [1, 2], 'tag_ids' => [5]])->assertOk();
        $this->post(route('inventory.filter.store'), ['category_ids' => [3]])->assertOk();

        $filter = InventoryUserFilter::query()->where('user_id', $user->id)->sole();
        $this->assertSame([3], $filter->category_ids);
        // Nicht mitgeschickte Felder bleiben unverändert
        $this->assertSame([5], $filter->tag_ids);
    }
}
