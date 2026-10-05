<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBasketArticle extends Model
{
    /** @use HasFactory<\Database\Factories\Artwork\Modules\Inventory\Models\ProductBasketArticleFactory> */
    use HasFactory;

    protected $fillable = [
        'product_basket_id',
        'article_id',
        'quantity'
    ];


    /**
     * @return BelongsTo<ProductBasket, $this>
     */
    public function productBasket(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductBasket::class, 'product_basket_id', 'id', 'product_baskets');
    }

    /**
     * @return BelongsTo<InventoryArticle, $this>
     */
    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryArticle::class, 'article_id', 'id', 'articles');
    }
}
