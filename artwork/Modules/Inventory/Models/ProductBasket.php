<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBasket extends Model
{
    /** @use HasFactory<\Database\Factories\Artwork\Modules\Inventory\Models\ProductBasketFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name'
    ];

    /**
     * @return HasMany<ProductBasketArticle, $this>
     */
    public function basketArticles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductBasketArticle::class, 'product_basket_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}
