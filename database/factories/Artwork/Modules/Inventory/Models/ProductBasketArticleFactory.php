<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\ProductBasket;
use Artwork\Modules\Inventory\Models\ProductBasketArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBasketArticle>
 */
class ProductBasketArticleFactory extends Factory
{
    protected $model = ProductBasketArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_basket_id' => ProductBasket::factory(),
            'article_id' => InventoryArticle::factory(),
        ];
    }
}
